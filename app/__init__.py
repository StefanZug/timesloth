# --- DIE ZERO-TOUCH AUTOMATIK (Sicher für mehrere Worker) ---
    with app.app_context():
        db.create_all()
        
        from .models import User
        from sqlalchemy.exc import IntegrityError

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
                # Falls ein anderer Worker schneller war, machen wir einen Rollback 
                # und ignorieren den Fehler einfach.
                db.session.rollback()
                print("ℹ️  Admin wurde bereits von einem anderen Prozess erstellt.")
        
    return app