from flask import Blueprint, render_template, redirect, url_for, request, flash, jsonify
from flask_login import login_user, logout_user, login_required, current_user
from . import db, bcrypt
from .models import User, LoginLog

auth = Blueprint('auth', __name__)

@auth.route('/login', methods=['GET', 'POST'])
def login():
    # Wenn schon eingeloggt, direkt zum Dashboard
    if current_user.is_authenticated:
        return redirect(url_for('main.dashboard'))
    
    # Login Versuch (POST)
    if request.method == 'POST':
        # Wir unterstützen sowohl JSON (für die App) als auch Form Data
        if request.is_json:
            data = request.get_json()
            # .lower() sorgt dafür, dass "Admin" am Handy als "admin" erkannt wird
            username = data.get('username', '').lower()
            password = data.get('password')
        else:
            username = request.form.get('username', '').lower()
            password = request.form.get('password')

        user = User.query.filter_by(username=username).first()
        
        if user and bcrypt.check_password_hash(user.password_hash, password):
            login_user(user, remember=True)
            
            # Loggen für User-Info
            log = LoginLog(user_id=user.id, ip_address=request.remote_addr)
            db.session.add(log)
            db.session.commit()
            
            if request.is_json:
                return jsonify({"status": "success"})
            return redirect(url_for('main.dashboard'))
        
        if request.is_json:
            return jsonify({"error": "Falsche Daten"}), 401
        flash('Login fehlgeschlagen. Prüfe User und Passwort.', 'danger')
        
    # WICHTIG: Diese Zeile muss AUßERHALB des 'if POST' Blocks stehen!
    # Sie wird ausgeführt, wenn die Seite einfach nur geladen wird (GET).
    return render_template('login.html')

@auth.route('/logout')
@login_required
def logout():
    logout_user()
    return redirect(url_for('auth.login'))

@auth.route('/change_password', methods=['POST'])
@login_required
def change_password():
    data = request.get_json()
    old_pw = data.get('old_password')
    new_pw = data.get('new_password')
    
    if not bcrypt.check_password_hash(current_user.password_hash, old_pw):
        return jsonify({"error": "Altes Passwort falsch"}), 400
    
    hashed_pw = bcrypt.generate_password_hash(new_pw).decode('utf-8')
    current_user.password_hash = hashed_pw
    db.session.commit()
    return jsonify({"status": "success", "message": "Passwort geändert"})