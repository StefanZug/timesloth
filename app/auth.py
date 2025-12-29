from flask import Blueprint, render_template, redirect, url_for, request, flash, jsonify
from flask_login import login_user, logout_user, login_required, current_user
from . import db, bcrypt
from .models import User, LoginLog

auth = Blueprint('auth', __name__)

@auth.route('/login', methods=['GET', 'POST'])
def login():
    if current_user.is_authenticated:
        return redirect(url_for('main.dashboard'))
    
    if request.method == 'POST':
        if request.is_json:
            data = request.get_json()
            # HIER: .lower() hinzufügen
            username = data.get('username', '').lower()
            password = data.get('password')
        else:
            # HIER: .lower() hinzufügen
            username = request.form.get('username', '').lower()
            password = request.form.get('password')

        user = User.query.filter_by(username=username).first()

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