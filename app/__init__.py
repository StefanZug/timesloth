import os
from flask import Flask
from flask_sqlalchemy import SQLAlchemy
from flask_bcrypt import Bcrypt
from flask_login import LoginManager

# Globale Instanzen
db = SQLAlchemy()
bcrypt = Bcrypt()
login_manager = LoginManager()
login_manager.login_view = 'auth.login' 
login_manager.login_message_category = 'info'

def create_app():
    app = Flask(__name__)
    
    # Config
    app.config['SECRET_KEY'] = os.environ.get('SECRET_KEY', 'timesloth_secret_key_change_me')
    
    # DB Pfad Logik (Robust für HAOS und Lokal)
    db_folder = os.environ.get('DB_FOLDER', './data')
    if not os.path.exists(db_folder):
        try:
            os.makedirs(db_folder)
        except OSError:
            pass 
            
    app.config['SQLALCHEMY_DATABASE_URI'] = f'sqlite:///{os.path.join(db_folder, "timesloth.db")}'
    app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False

    # Init Extensions
    db.init_app(app)
    bcrypt.init_app(app)
    login_manager.init_app(app)

    # Blueprints importieren
    # (Innerhalb der Funktion, um "Circular Imports" zu verhindern)
    from .routes import main
    from .auth import auth

    app.register_blueprint(main)
    app.register_blueprint(auth)

    # --- DIE ZERO-TOUCH AUTOMATIK ---
    with app.app_context():
        db.create_all()
        
        # Prüfen, ob User existieren
        from .models import User
        if not User.query.first():
            print("⚠️  Datenbank ist leer. Erstelle Default-Admin...")
            
            # Default Credentials
            default_user = "admin"
            default_pass = "admin"
            
            hashed_pw = bcrypt.generate_password_hash(default_pass).decode('utf-8')
            admin = User(username=default_user, password_hash=hashed_pw, is_admin=True)
            
            db.session.add(admin)
            db.session.commit()
            print(f"✅  Admin erstellt! User: '{default_user}' / Pass: '{default_pass}'")
        
    return app