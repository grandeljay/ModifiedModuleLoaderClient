# Installation
1. Melde Dich im Adminbereich an.
2. Gehe im Menü zu **Module > System Module**.
3. Wähle dort das Modul **Massenverarbeitung** aus und klicke auf installieren.
4. Lies die Installationsanleitungen der abhängigen Module (falls vorhanden).

Bei der Installation werden keine Tabellen in der Datenbank hinzugefügt und keine Tabellenstrukturen verändert.

## Einstellungen für Rechnung und Lieferschein
Das Modul **Massenverarbeitung** verwendet zur Erzeugung von Rechnungen und Lieferscheinen das Modul **Pdf Brief, Rechnung und Lieferschein**. Damit die Dokumente mit den richtigen Daten *(Domainname, Absenderadresse, Firmenbezeichnung, etc.)* erstellt werden, müssen in der Sprachdatei `rth_pdf_bill.php` die entsprechenden Daten hinterlegt werden. Die Sprachdatei befindet sich jeweils pro Template in folgendem Verzeichnis:

- /templates/*TEMPLATE*/lang/*SPRACHE*/rth_pdf_bill.php

---

# Deinstallation
1. Melde Dich im Adminbereich an.
2. Gehe im Menü zu **Module > System Module**.
3. Wähle dort das Modul **Massenverarbeitung** aus und klicke auf deinstallieren.

Bei der Deinstallation werden keine Tabellen in der Datenbank entfernt oder gelöscht und keine Tabellenstrukturen verändert.
