from flask import Blueprint, render_template, request, jsonify, current_app
from flask_login import login_required, current_user
from . import db, bcrypt
from .models import User, Entry, GlobalHoliday
import json
from datetime import datetime

main = Blueprint('main', __name__)

# --- ADMIN BEREICH (Privacy Shield aktiv) ---

@main.route('/admin')
@login_required
def admin_panel():
    if not current_user.is_admin:
        return "Nice try. Zugriff verweigert.", 403
    # Admin sieht nur User-Liste, KEINE Entries laden!
    users = User.query.all()
    return render_template('admin.html', users=users)

@main.route('/admin/create_user', methods=['POST'])
@login_required
def admin_create_user():
    if not current_user.is_admin: return jsonify({"error": "Nein"}), 403
    data = request.get_json()
    
    if User.query.filter_by(username=data['username']).first():
        return jsonify({"error": "User existiert schon"}), 400
        
    hashed_pw = bcrypt.generate_password_hash(data['password']).decode('utf-8')
    user = User(username=data['username'], password_hash=hashed_pw, is_admin=data.get('is_admin', False))
    db.session.add(user)
    db.session.commit()
    return jsonify({"status": "User angelegt"})

@main.route('/admin/delete_user/<int:user_id>', methods=['POST'])
@login_required
def admin_delete_user(user_id):
    if not current_user.is_admin: return jsonify({"error": "Nein"}), 403
    user = User.query.get_or_404(user_id)
    # SQLAlchemy cascade löscht automatisch alle sensiblen Zeitdaten!
    db.session.delete(user)
    db.session.commit()
    return jsonify({"status": "Gelöscht. Daten sind futsch."})

@main.route('/admin/reset_pw/<int:user_id>', methods=['POST'])
@login_required
def admin_reset_pw(user_id):
    if not current_user.is_admin: return jsonify({"error": "Nein"}), 403
    user = User.query.get_or_404(user_id)
    # Default Reset PW
    user.password_hash = bcrypt.generate_password_hash("changeme123").decode('utf-8')
    db.session.commit()
    return jsonify({"status": "PW ist jetzt 'changeme123'"})

# --- USER APP BEREICH ---

@main.route('/')
@login_required
def dashboard():
    return render_template('dashboard.html', user=current_user)

@main.route('/api/get_entries')
@login_required
def get_entries():
    month = request.args.get('month') # YYYY-MM
    # Privacy: Lade NUR Daten vom aktuellen User
    entries = Entry.query.filter_by(user_id=current_user.id).filter(Entry.date_str.like(f"{month}%")).all()
    
    result = []
    for e in entries:
        # JSON Strings zurück in Objekte wandeln
        office = json.loads(e.office_times or '[]')
        home = json.loads(e.home_times or '[]')
        
        result.append({
            "date": e.date_str,
            "office_times": office,
            "home_times": home,
            "doctor_minutes": e.doctor_minutes,
            "is_holiday": e.is_holiday,
            "is_vacation": e.is_vacation,
            "is_sick": e.is_sick,
            "comment": e.comment
        })
    return jsonify(result)

@main.route('/api/save_entry', methods=['POST'])
@login_required
def save_entry():
    data = request.get_json()
    date_str = data['date']
    
    entry = Entry.query.filter_by(user_id=current_user.id, date_str=date_str).first()
    if not entry:
        entry = Entry(user_id=current_user.id, date_str=date_str)
        db.session.add(entry)
    
    # Speichere komplexe Zeitblöcke als JSON String
    entry.office_times = json.dumps(data.get('office_times', []))
    entry.home_times = json.dumps(data.get('home_times', []))
    entry.doctor_minutes = int(data.get('doctor_minutes', 0))
    
    entry.is_holiday = data.get('is_holiday', False)
    entry.is_vacation = data.get('is_vacation', False)
    entry.is_sick = data.get('is_sick', False)
    entry.comment = data.get('comment', '')
    
    db.session.commit()
    return jsonify({"status": "Saved"})

# Settings speichern (Darkmode, Sortierung)
@main.route('/api/settings', methods=['POST'])
@login_required
def save_settings():
    data = request.get_json()
    for key, val in data.items():
        current_user.set_setting(key, val)
    db.session.commit()
    return jsonify({"status": "Settings Saved"})