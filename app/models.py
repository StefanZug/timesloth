from . import db, login_manager
from flask_login import UserMixin
from datetime import datetime
import json

@login_manager.user_loader
def load_user(user_id):
    return User.query.get(int(user_id))

class User(db.Model, UserMixin):
    id = db.Column(db.Integer, primary_key=True)
    username = db.Column(db.String(50), unique=True, nullable=False)
    password_hash = db.Column(db.String(128), nullable=False)
    is_admin = db.Column(db.Boolean, default=False)
    
    # Settings als JSON (Darkmode, Sortierung Home/Office etc.)
    settings = db.Column(db.Text, default='{}')
    
    # Beziehung zu Einträgen (löscht Einträge, wenn User gelöscht wird)
    entries = db.relationship('Entry', backref='author', lazy=True, cascade="all, delete-orphan")

    def set_setting(self, key, value):
        s = json.loads(self.settings or '{}')
        s[key] = value
        self.settings = json.dumps(s)

    def get_setting(self, key, default=None):
        s = json.loads(self.settings or '{}')
        return s.get(key, default)

class Entry(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('user.id'), nullable=False)
    date_str = db.Column(db.String(10), nullable=False) # YYYY-MM-DD

    # JSON Felder für flexible Zeitblöcke: 
    # Bsp: [{"start": "08:00", "end": "12:00"}, {"start": "13:00", "end": "16:00"}]
    office_times = db.Column(db.Text, default='[]') 
    home_times = db.Column(db.Text, default='[]')
    
    # Arzt/Amtsweg in Minuten
    doctor_minutes = db.Column(db.Integer, default=0) 

    # Status Flags
    is_holiday = db.Column(db.Boolean, default=False) # Globaler Feiertag
    is_vacation = db.Column(db.Boolean, default=False)
    is_sick = db.Column(db.Boolean, default=False)
    
    # Override: Wenn User am Feiertag arbeitet (F wegklickt)
    holiday_override = db.Column(db.Boolean, default=False)

    comment = db.Column(db.Text, default='')

    # Verhindert doppelte Einträge pro Tag und User
    __table_args__ = (db.UniqueConstraint('user_id', 'date_str', name='_user_date_uc'),)

class GlobalHoliday(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    date_str = db.Column(db.String(10), unique=True)
    name = db.Column(db.String(100))

class LoginLog(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('user.id'))
    timestamp = db.Column(db.DateTime, default=datetime.utcnow)
    ip_address = db.Column(db.String(50))
    user_agent = db.Column(db.String(200))