const int buzzer = PD_0;
const int bouton = PD_1;
int lastEtat = -1;

void setup() {
  pinMode(buzzer, OUTPUT);
  pinMode(bouton, INPUT);
  Serial.begin(9600);
}

void loop() {
  int etat = digitalRead(bouton);

  if (etat != lastEtat){
    
    if (etat == HIGH) {
      tone(buzzer, 3000);
    } else {
      noTone(buzzer);
    }
    
    Serial.println(etat);
    lastEtat = etat;
  }
  
  delay(20);
}