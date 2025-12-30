import os
from flask import Flask
from flask_sqlalchemy import SQLAlchemy
from flask_bcrypt import Bcrypt
from flask_login import LoginManager
from flask_limiter import Limiter
from flask_limiter.util import get_remote_address
from sqlalchemy.exc import IntegrityError
from datetime import timedelta

# Globale Instanzen
db = SQLAlchemy()
bcrypt = Bcrypt()
login_manager = LoginManager()
limiter = Limiter(key_func=get_remote_address)

login_manager.login_view = 'auth.login' 
login_manager.login_message_category = 'info'

def create_app():
    app = Flask(__name__)
    
    # Config
    # Das hier holt den Key aus HA (via run.sh). Der String hinten ist nur Fallback.
    app.config['SECRET_KEY'] = os.environ.get('SECRET_KEY', 'timesloth_secret_key_change_me')
    
    # FIX für die Warnung: Flask-Limiter braucht RATELIMIT_STORAGE_URI
    # Auch hier: Holt die URL aus HA, sonst nimmt er memory://
    redis_url = os.environ.get('RATELIMIT_STORAGE_URL', 'memory://')
    app.config['RATELIMIT_STORAGE_URI'] = redis_url
    app.config['RATELIMIT_STORAGE_URL'] = redis_url
    
    # DB Pfad Logik
    db_folder = os.environ.get('DB_FOLDER', './data')
    if not os.path.exists(db_folder):
        try:
            os.makedirs(db_folder)
        except OSError:
            pass 
            
    # DB Pfad Logik
    # Wir holen den Pfad, entfernen evtl. trailing slashes
    db_folder = os.environ.get('DB_FOLDER', '/data').rstrip('/')
    
    db_path = os.path.join(db_folder, "timesloth.db")
    print(f"🐞 DEBUG: Nutze Datenbank Pfad: {db_path}") # Damit sehen wir es im Log!
    
    # 4 Slashes für absoluten Pfad auf Unix/Linux
    app.config['SQLALCHEMY_DATABASE_URI'] = f'sqlite:///{db_path}'
    app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False

    # Init Extensions
    db.init_app(app)
    bcrypt.init_app(app)
    login_manager.init_app(app)
    limiter.init_app(app)

    # Blueprints importieren
    from .routes import main
    from .auth import auth

    app.register_blueprint(main)
    app.register_blueprint(auth)

    # --- DIE ZERO-TOUCH AUTOMATIK (Sicher für mehrere Worker) ---
    with app.app_context():
        db.create_all()
        
        # --- SIMPLE MIGRATION: user_agent Spalte hinzufügen falls fehlt ---
        from sqlalchemy import text
        try:
            with db.engine.connect() as conn:
                conn.execute(text("SELECT user_agent FROM login_log LIMIT 1"))
        except Exception:
            print("⚠️ Migriere DB: Füge 'user_agent' Spalte hinzu...")
            try:
                with db.engine.connect() as conn:
                    conn.execute(text("ALTER TABLE login_log ADD COLUMN user_agent VARCHAR(200)"))
                    conn.commit()
            except Exception as e:
                print(f"Migration Fehler (kann ignoriert werden bei neuer DB): {e}")
        
        # Import hier drin um Zirkelbezug zu vermeiden
        from .models import User

        # Wir suchen gezielt nach dem admin
        admin_exists = User.query.filter_by(username="admin").first()
        
        if not admin_exists:
            try:
                print("⚠️  Erstelle Default-Admin...")
                default_user = "admin"
                default_pass = "admin"
                
                hashed_pw = bcrypt.generate_password_hash(default_pass).decode('utf-8')
                admin = User(username=default_user, password_hash=hashed_pw, is_admin=True)
                
                db.session.add(admin)
                db.session.commit()
                print(f"✅  Admin erfolgreich erstellt!")
            except IntegrityError:
                # Falls ein anderer Worker schneller war
                db.session.rollback()
                print("ℹ️  Admin wurde bereits von einem anderen Prozess erstellt.")
        
    return app