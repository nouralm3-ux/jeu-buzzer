import serial
import mysql.connector

ser = serial.Serial('COM3', 9600)

db = mysql.connector.connect(
    host="178.33.122.21",
    user="dade64253",
    password="hbxfdIxJRzZPd5nq3wEuxuyF",
    database="hangardb_dade64253"
)

cursor = db.cursor()

last = None

while True:
    valeur = ser.readline().decode().strip()

    if valeur != last:

        print("Changement détecté :", valeur)

        cursor.execute("""
            UPDATE g8b_etat_actuel
            SET etat = %s
            WHERE id = 1
        """, (valeur,))

        db.commit()

        last = valeur