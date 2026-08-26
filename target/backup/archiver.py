"""Pomožni modul za backup.py. Simulira izdelavo rezervne kopije."""
import datetime


def run_backup():
    print(f"[backup] Zagon rezervne kopije: {datetime.datetime.now()}")
    print("[backup] Rezervna kopija je bila (simulirano) uspešno izdelana.")
