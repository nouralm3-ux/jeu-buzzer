"""
Pont série → SensorHub
Lit les données de la carte Tiva C via USB/COM et les envoie au site PHP.

Installation :
    pip install pyserial requests

Usage :
    python bridge.py COM3 115200 1
    python bridge.py COM3 115200 1 http://localhost/jeu-buzzer/api_push.php
    (port, baud_rate, sensor_id, url_optionnelle)
"""

import serial
import requests
import time
import sys
import argparse

DEFAULT_API_URL = 'http://localhost/fin%20a1/api_push.php'
API_KEY = 'tiva_bridge_2024'


def find_tiva_port():
    """Essaie de détecter automatiquement le port de la Tiva."""
    import serial.tools.list_ports
    ports = serial.tools.list_ports.comports()
    for p in ports:
        if 'tiva' in p.description.lower() or 'stellaris' in p.description.lower() \
                or 'xds110' in p.description.lower() or 'icdi' in p.description.lower():
            return p.device
    # Retourne la liste pour que l'utilisateur choisisse
    if ports:
        print("Ports série disponibles :")
        for p in ports:
            print(f"  {p.device} — {p.description}")
    return None


def main():
    parser = argparse.ArgumentParser(description='Pont Tiva C → SensorHub')
    parser.add_argument('port',      nargs='?', help='Port COM (ex: COM3)')
    parser.add_argument('baud_rate', nargs='?', type=int, default=115200, help='Vitesse (défaut: 115200)')
    parser.add_argument('sensor_id', nargs='?', type=int, default=1,      help='ID du capteur dans le dashboard (défaut: 1)')
    parser.add_argument('api_url',   nargs='?', default=DEFAULT_API_URL,  help='URL de api_push.php (défaut: localhost/fin a1)')
    args = parser.parse_args()

    port    = args.port
    api_url = args.api_url
    if not port:
        port = find_tiva_port()
        if not port:
            print("Erreur : aucun port COM détecté. Précisez-le en argument.")
            print("  Exemple : python bridge.py COM3 115200 1")
            sys.exit(1)
        print(f"Port détecté automatiquement : {port}")

    baud = args.baud_rate
    sid  = args.sensor_id

    print(f"Connexion à {port} — {baud} bps — capteur ID={sid}")
    print(f"Envoi vers {api_url}")
    print("Ctrl+C pour arrêter.\n")

    try:
        ser = serial.Serial(port, baud, timeout=1)
    except serial.SerialException as e:
        print(f"Erreur ouverture port : {e}")
        sys.exit(1)

    errors = 0
    while True:
        try:
            raw = ser.readline()
            if not raw:
                continue
            line = raw.decode('utf-8', errors='replace').strip()
            if not line:
                continue

            print(f"RX  {line}")

            try:
                resp = requests.post(api_url, data={
                    'key':       API_KEY,
                    'sensor_id': sid,
                    'data':      line,
                }, timeout=3)
                if resp.status_code == 200:
                    errors = 0
                else:
                    print(f"  → HTTP {resp.status_code}: {resp.text[:80]}")
            except requests.RequestException as e:
                errors += 1
                print(f"  → Erreur réseau ({errors}) : {e}")
                if errors >= 10:
                    print("Trop d'erreurs réseau. Vérifiez que XAMPP est lancé.")
                    break

        except serial.SerialException as e:
            print(f"Erreur série : {e}")
            break
        except KeyboardInterrupt:
            print("\nArrêt.")
            break

    ser.close()


if __name__ == '__main__':
    main()
