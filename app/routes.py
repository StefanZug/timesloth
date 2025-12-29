from flask import Blueprint, render_template, request, jsonify, current_app
from flask_login import login_required, current_user
from . import db, bcrypt
from .models import User, Entry, GlobalHoliday
import json
from datetime import datetime

main = Blueprint('main', __name__)

# --- ADMIN BEREICH ---

@main.route('/admin')
@login_required
def admin_panel():
    if not current_user.is_admin:
        return "Nice try. Zugriff verweigert.", 403
    users = User.query.all()
    holidays = GlobalHoliday.query.order_by(GlobalHoliday.date_str).all()
    return render_template('admin.html', users=users, holidays=holidays)

@main.route('/admin/holiday', methods=['POST'])
@login_required
def admin_add_holiday():
    if not current_user.is_admin: return jsonify({"error": "Nein"}), 403
    data = request.get_json()
    
    # Check ob existiert
    exists = GlobalHoliday.query.filter_by(date_str=data['date']).first()
    if exists:
        return jsonify({"error": "Datum schon vorhanden"}), 400
        
    gh = GlobalHoliday(date_str=data['date'], name=data['name'])
    db.session.add(gh)
    db.session.commit()
    return jsonify({"status": "created", "id": gh.id})

@main.route('/admin/holiday/<int:id>', methods=['DELETE'])
@login_required
def admin_delete_holiday(id):
    if not current_user.is_admin: return jsonify({"error": "Nein"}), 403
    gh = GlobalHoliday.query.get_or_404(id)
    db.session.delete(gh)
    db.session.commit()
    return jsonify({"status": "deleted"})

@main.route('/admin/create_user', methods=['POST'])
@login_required
def admin_create_user():
    if not current_user.is_admin: return jsonify({"error": "Nein"}), 403
    data = request.get_json()
    username_clean = data['username'].lower()
    if User.query.filter_by(username=data['username']).first():
        return jsonify({"error": "User existiert schon"}), 400 
    hashed_pw = bcrypt.generate_password_hash(data['password']).decode('utf-8')
    user = User(username=username_clean, password_hash=hashed_pw, is_admin=data.get('is_admin', False))
    db.session.add(user)
    db.session.commit()
    return jsonify({"status": "User angelegt"})

@main.route('/admin/delete_user/<int:user_id>', methods=['POST'])
@login_required
def admin_delete_user(user_id):
    if not current_user.is_admin: return jsonify({"error": "Nein"}), 403
    user = User.query.get_or_404(user_id)
    db.session.delete(user)
    db.session.commit()
    return jsonify({"status": "Gelöscht."})

@main.route('/admin/reset_pw/<int:user_id>', methods=['POST'])
@login_required
def admin_reset_pw(user_id):
    if not current_user.is_admin: return jsonify({"error": "Nein"}), 403
    user = User.query.get_or_404(user_id)
    user.password_hash = bcrypt.generate_password_hash("changeme123").decode('utf-8')
    db.session.commit()
    return jsonify({"status": "PW Reset done"})


# --- USER APP BEREICH ---

@main.route('/')
@login_required
def dashboard():
    return render_template('dashboard.html', user=current_user)

@main.route('/api/get_entries')
@login_required
def get_entries():
    month = request.args.get('month') # YYYY-MM
    
    # 1. User Einträge laden
    entries = Entry.query.filter_by(user_id=current_user.id).filter(Entry.date_str.like(f"{month}%")).all()
    
    # 2. Globale Feiertage laden
    holidays = GlobalHoliday.query.filter(GlobalHoliday.date_str.like(f"{month}%")).all()
    holiday_map = {h.date_str: h.name for h in holidays}
    
    result = []
    for e in entries:
        # Wir laden ALLES aus office_times (da speichern wir jetzt die Blocks)
        blocks = json.loads(e.office_times or '[]')
        
        result.append({
            "date": e.date_str,
            "blocks": blocks,
            "status": 'F' if e.is_holiday else ('U' if e.is_vacation else ('K' if e.is_sick else None)),
            "comment": e.comment
        })
        
    # NEU: Settings mitladen!
    user_settings = json.loads(current_user.settings or '{}')
        
    return jsonify({
        "entries": result,
        "holidays": holiday_map,  # <--- HIER WAR DAS KOMMA, DAS GEFEHLT HAT!
        "settings": user_settings
    })

@main.route('/api/save_entry', methods=['POST'])
@login_required
def save_entry():
    data = request.get_json()
    date_str = data['date']
    
    entry = Entry.query.filter_by(user_id=current_user.id, date_str=date_str).first()
    if not entry:
        entry = Entry(user_id=current_user.id, date_str=date_str)
        db.session.add(entry)
    
    # Wir speichern das komplette Block-Array einfach in 'office_times'
    entry.office_times = json.dumps(data.get('blocks', []))
    
    status = data.get('status')
    entry.is_holiday = (status == 'F')
    entry.is_vacation = (status == 'U')
    entry.is_sick = (status == 'K')
    
    entry.comment = data.get('comment', '')
    
    db.session.commit()
    return jsonify({"status": "Saved"})

@main.route('/api/settings', methods=['POST'])
@login_required
def save_settings():
    data = request.get_json()
    for key, val in data.items():
        current_user.set_setting(key, val)
    db.session.commit()
    return jsonify({"status": "Settings Saved"})