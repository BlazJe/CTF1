"""Nordvel CTF - strežnik za oddajo zastavic."""

import base64
import hashlib
import json
import os
import sqlite3
from datetime import datetime, timezone

from flask import Flask, g, jsonify, redirect, render_template, request, session, url_for

DB_PATH = os.environ.get("SCOREBOARD_DB", "/data/scoreboard.db")

# Naslovi okolja. Na enem mestu, ker se ob spremembi podomrežja v
# docker-compose.yml popravijo tudi vsi prikazi v vmesniku.
TARGET_URL = os.environ.get("TARGET_URL", "http://ctf.lan:8080")
TARGET_IP = os.environ.get("TARGET_IP", "172.28.0.20")
HOST_IP = os.environ.get("HOST_IP", "172.28.0.1")

app = Flask(__name__)
app.secret_key = os.environ.get("SCOREBOARD_SECRET", os.urandom(32))


# --------------------------------------------------------------------------
# Naloge
#
# opis      - samo cilj naloge. Ne pove, kje iskati, in ne imenuje orodja.
# orodje    - skrito, dokler ni odprt prvi namig
# namig 1   - orodje in kratek pokazatelj, nikoli postopek (stane nekaj točk)
# namig 2   - celoten postopek reševanja; kdor ga odpre, za nalogo ne dobi točk
# gradivo   - povezava za učenje, vidna šele po rešeni nalogi
# --------------------------------------------------------------------------

TASKS_FILE = os.environ.get("TASKS_FILE", "/app/secrets/tasks.b64")


def load_tasks():
    """Naloge so v repozitoriju shranjene zakodirane, da opisi, namigi in
    zgoscene vrednosti odgovorov niso berljivi z golim pregledom datotek."""
    with open(TASKS_FILE, encoding="utf-8") as fh:
        return json.loads(base64.b64decode(fh.read()).decode("utf-8"))


TASKS = load_tasks()
TOTAL_POINTS = sum(t["points"] for t in TASKS)


# --------------------------------------------------------------------------
# Baza
# --------------------------------------------------------------------------

SCHEMA = """
CREATE TABLE IF NOT EXISTS teams (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS tasks (
    id            INTEGER PRIMARY KEY,
    order_num     INTEGER NOT NULL UNIQUE,
    title         TEXT NOT NULL,
    description   TEXT NOT NULL,
    tool_hint     TEXT NOT NULL,
    hint1         TEXT NOT NULL,
    hint1_cost    INTEGER NOT NULL,
    hint2         TEXT NOT NULL,
    points        INTEGER NOT NULL,
    answer_sha256 TEXT NOT NULL,
    learn_label   TEXT NOT NULL,
    learn_url     TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS progress (
    team_id      INTEGER NOT NULL,
    task_id      INTEGER NOT NULL,
    solved_at    TEXT NOT NULL,
    answer_plain TEXT NOT NULL,
    PRIMARY KEY (team_id, task_id)
);

CREATE TABLE IF NOT EXISTS hints_used (
    team_id INTEGER NOT NULL,
    task_id INTEGER NOT NULL,
    level   INTEGER NOT NULL,
    used_at TEXT NOT NULL,
    PRIMARY KEY (team_id, task_id, level)
);
"""


def get_db():
    if "db" not in g:
        g.db = sqlite3.connect(DB_PATH)
        g.db.row_factory = sqlite3.Row
    return g.db


@app.teardown_appcontext
def close_db(exception=None):
    db = g.pop("db", None)
    if db is not None:
        db.close()


def init_db():
    os.makedirs(os.path.dirname(DB_PATH), exist_ok=True)
    db = sqlite3.connect(DB_PATH)
    db.executescript(SCHEMA)

    for task in TASKS:
        db.execute(
            """INSERT INTO tasks (id, order_num, title, description, tool_hint,
                                  hint1, hint1_cost, hint2, points, answer_sha256,
                                  learn_label, learn_url)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
               ON CONFLICT(id) DO UPDATE SET
                    order_num=excluded.order_num,
                    title=excluded.title,
                    description=excluded.description,
                    tool_hint=excluded.tool_hint,
                    hint1=excluded.hint1,
                    hint1_cost=excluded.hint1_cost,
                    hint2=excluded.hint2,
                    points=excluded.points,
                    answer_sha256=excluded.answer_sha256,
                    learn_label=excluded.learn_label,
                    learn_url=excluded.learn_url""",
            (
                task["order"],
                task["order"],
                task["title"],
                task["description"],
                task["tool"],
                task["hint1"],
                task["hint1_cost"],
                task["hint2"],
                task["points"],
                task["answer_sha256"],
                task["learn_label"],
                task["learn_url"],
            ),
        )

    db.commit()
    db.close()


# --------------------------------------------------------------------------
# Stanje ekipe
# --------------------------------------------------------------------------

def normalise(answer: str) -> str:
    return answer.strip()


def build_state(team_id: int) -> dict:
    """Celotno stanje plošče za eno ekipo. Odloča strežnik, ne brskalnik."""
    db = get_db()

    tasks = db.execute("SELECT * FROM tasks ORDER BY order_num").fetchall()

    solved = {
        row["task_id"]: row
        for row in db.execute(
            "SELECT task_id, solved_at, answer_plain FROM progress WHERE team_id = ?",
            (team_id,),
        ).fetchall()
    }
    hints = {
        (row["task_id"], row["level"])
        for row in db.execute(
            "SELECT task_id, level FROM hints_used WHERE team_id = ?", (team_id,)
        ).fetchall()
    }

    out_tasks = []
    score = 0
    previous_solved = True  # prva naloga je vedno odklenjena

    for task in tasks:
        tid = task["id"]
        is_solved = tid in solved
        h1 = (tid, 1) in hints
        h2 = (tid, 2) in hints

        if h2:
            earned = 0
        elif is_solved:
            earned = task["points"] - (task["hint1_cost"] if h1 else 0)
        else:
            earned = 0
        if is_solved:
            score += earned

        out_tasks.append(
            {
                "id": tid,
                "order": task["order_num"],
                "title": task["title"],
                "description": task["description"],
                # Orodje je del prvega namiga, zato ga prej ne pošiljamo.
                "tool": task["tool_hint"] if (h1 or is_solved) else None,
                "points": task["points"],
                "unlocked": previous_solved,
                "solved": is_solved,
                "answer": solved[tid]["answer_plain"] if is_solved else None,
                "earned": earned if is_solved else None,
                "hint1_cost": task["hint1_cost"],
                "hint1_revealed": h1,
                "hint1": task["hint1"] if h1 else None,
                "hint2_revealed": h2,
                "hint2": task["hint2"] if h2 else None,
                "zeroed": h2,
                "learn_label": task["learn_label"] if is_solved else None,
                "learn_url": task["learn_url"] if is_solved else None,
            }
        )

        previous_solved = is_solved

    solved_count = len(solved)
    total = len(tasks)

    return {
        "team": session.get("team_name", ""),
        "tasks": out_tasks,
        "score": score,
        "max_score": TOTAL_POINTS,
        "solved_count": solved_count,
        "total": total,
        "percent": round(solved_count / total * 100) if total else 0,
        "finished": total > 0 and solved_count == total,
        "env": {"target_url": TARGET_URL, "target_ip": TARGET_IP, "host_ip": HOST_IP},
    }


def current_team():
    return session.get("team_id")


def task_by_id(task_id: int):
    return get_db().execute("SELECT * FROM tasks WHERE id = ?", (task_id,)).fetchone()


def is_unlocked(team_id: int, task) -> bool:
    """Naloga je odklenjena, ko je rešena neposredno prejšnja."""
    if task["order_num"] == 1:
        return True
    previous = get_db().execute(
        "SELECT id FROM tasks WHERE order_num = ?", (task["order_num"] - 1,)
    ).fetchone()
    if previous is None:
        return False
    return get_db().execute(
        "SELECT 1 FROM progress WHERE team_id = ? AND task_id = ?",
        (team_id, previous["id"]),
    ).fetchone() is not None


def hint_revealed(team_id: int, task_id: int, level: int) -> bool:
    return get_db().execute(
        "SELECT 1 FROM hints_used WHERE team_id = ? AND task_id = ? AND level = ?",
        (team_id, task_id, level),
    ).fetchone() is not None


# --------------------------------------------------------------------------
# Poti
# --------------------------------------------------------------------------

@app.route("/", methods=["GET", "POST"])
def start():
    if current_team():
        return redirect(url_for("board"))

    error = None
    if request.method == "POST":
        name = (request.form.get("team_name") or "").strip()
        if not name:
            error = "Vpišite ime ekipe."
        elif len(name) > 40:
            error = "Ime ekipe naj ima največ 40 znakov."
        else:
            db = get_db()
            row = db.execute("SELECT id FROM teams WHERE name = ?", (name,)).fetchone()
            if row:
                team_id = row["id"]
            else:
                cur = db.execute(
                    "INSERT INTO teams (name, created_at) VALUES (?, ?)",
                    (name, datetime.now(timezone.utc).isoformat()),
                )
                db.commit()
                team_id = cur.lastrowid
            session["team_id"] = team_id
            session["team_name"] = name
            return redirect(url_for("board"))

    return render_template("start.html", error=error, total_points=TOTAL_POINTS,
                           total_tasks=len(TASKS))


@app.route("/odjava")
def logout():
    session.clear()
    return redirect(url_for("start"))


@app.route("/naloge")
def board():
    team_id = current_team()
    if not team_id:
        return redirect(url_for("start"))
    state = build_state(team_id)
    return render_template("board.html", state=state, state_json=json.dumps(state))


@app.route("/api/stanje")
def api_state():
    team_id = current_team()
    if not team_id:
        return jsonify({"ok": False, "error": "Seja je potekla."}), 401
    return jsonify({"ok": True, "state": build_state(team_id)})


@app.route("/api/oddaja/<int:task_id>", methods=["POST"])
def api_submit(task_id):
    team_id = current_team()
    if not team_id:
        return jsonify({"ok": False, "error": "Seja je potekla."}), 401

    task = task_by_id(task_id)
    if task is None:
        return jsonify({"ok": False, "error": "Naloga ne obstaja."}), 404

    if not is_unlocked(team_id, task):
        return jsonify({"ok": False, "error": "Ta naloga še ni odklenjena."}), 403

    db = get_db()
    already = db.execute(
        "SELECT 1 FROM progress WHERE team_id = ? AND task_id = ?", (team_id, task_id)
    ).fetchone()
    if already:
        return jsonify({"ok": True, "correct": True, "message": "Naloga je že rešena.",
                        "state": build_state(team_id)})

    answer = normalise((request.get_json(silent=True) or {}).get("answer", ""))
    if not answer:
        return jsonify({"ok": False, "error": "Vpišite odgovor."}), 400

    correct = hashlib.sha256(answer.encode()).hexdigest() == task["answer_sha256"]

    if correct:
        db.execute(
            "INSERT INTO progress (team_id, task_id, solved_at, answer_plain) VALUES (?,?,?,?)",
            (team_id, task_id, datetime.now(timezone.utc).isoformat(), answer),
        )
        db.commit()
        message = "Pravilno."
    else:
        message = "Odgovor ni pravilen. Poskusi znova."

    return jsonify({"ok": True, "correct": correct, "message": message,
                    "state": build_state(team_id)})


@app.route("/api/namig/<int:task_id>/<int:level>", methods=["POST"])
def api_hint(task_id, level):
    team_id = current_team()
    if not team_id:
        return jsonify({"ok": False, "error": "Seja je potekla."}), 401

    if level not in (1, 2):
        return jsonify({"ok": False, "error": "Neveljaven namig."}), 400

    task = task_by_id(task_id)
    if task is None:
        return jsonify({"ok": False, "error": "Naloga ne obstaja."}), 404

    if not is_unlocked(team_id, task):
        return jsonify({"ok": False, "error": "Ta naloga še ni odklenjena."}), 403

    # Drugi namig je na voljo šele, ko je odprt prvi.
    if level == 2 and not hint_revealed(team_id, task_id, 1):
        return jsonify({"ok": False, "error": "Najprej odprite prvi namig."}), 403

    get_db().execute(
        "INSERT OR IGNORE INTO hints_used (team_id, task_id, level, used_at) VALUES (?,?,?,?)",
        (team_id, task_id, level, datetime.now(timezone.utc).isoformat()),
    )
    get_db().commit()

    return jsonify({"ok": True, "state": build_state(team_id)})


if __name__ == "__main__":
    init_db()
    app.run(host="0.0.0.0", port=8000)
