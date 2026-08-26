#!/usr/bin/env python3
"""Interni backup skript Nordvel d.o.o. - zaganja ga devops preko sudo."""
import sys

sys.path.insert(0, "/opt/backup/lib")
import archiver  # noqa: E402

if __name__ == "__main__":
    archiver.run_backup()
