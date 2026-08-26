FROM python:3.12-slim

WORKDIR /app

COPY scoreboard/requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY scoreboard/ /app/

# Naloge so zakodirane, da opisi, namigi in zgoscene vrednosti odgovorov
# niso berljivi z golim pregledom repozitorija.
COPY secrets/tasks.b64 /app/secrets/tasks.b64

RUN mkdir -p /data
VOLUME /data

EXPOSE 8000

CMD ["python", "app.py"]
