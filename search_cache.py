#!/usr/bin/env python3

import argparse
import json
import sqlite3
import subprocess
from datetime import datetime, timezone
from pathlib import Path


def parse_arguments():
    parser = argparse.ArgumentParser(description="Cache and search extracted PDF text.")
    parser.add_argument("--database", required=True)
    parser.add_argument("--root", required=True)
    parser.add_argument("--year", required=True)
    parser.add_argument("--query", required=True)
    return parser.parse_args()


def extract_text(pdf_path):
    result = subprocess.run(
        ["pdftotext", str(pdf_path), "-"],
        stdout=subprocess.PIPE,
        stderr=subprocess.DEVNULL,
        check=False,
    )
    return result.stdout.decode("utf-8", errors="replace")


def main():
    arguments = parse_arguments()
    root = Path(arguments.root).resolve()
    year_directory = root / arguments.year

    if not arguments.year.isdigit() or len(arguments.year) != 4 or not year_directory.is_dir():
        raise ValueError("Invalid year directory")

    pdf_files = sorted(
        path
        for path in year_directory.rglob("*")
        if path.is_file() and path.suffix.lower() == ".pdf"
    )

    connection = sqlite3.connect(arguments.database, timeout=30)
    connection.execute("PRAGMA journal_mode = WAL")
    connection.execute("PRAGMA synchronous = NORMAL")
    connection.execute(
        """
        CREATE TABLE IF NOT EXISTS pdf_texts (
            path TEXT PRIMARY KEY,
            mtime_ns INTEGER NOT NULL,
            size INTEGER NOT NULL,
            content TEXT NOT NULL,
            indexed_at TEXT NOT NULL
        )
        """
    )

    current_paths = set()
    indexed_at = datetime.now(timezone.utc).isoformat()

    for pdf_path in pdf_files:
        relative_path = pdf_path.relative_to(root).as_posix()
        current_paths.add(relative_path)
        file_stat = pdf_path.stat()
        cached = connection.execute(
            "SELECT mtime_ns, size FROM pdf_texts WHERE path = ?",
            (relative_path,),
        ).fetchone()

        if cached == (file_stat.st_mtime_ns, file_stat.st_size):
            continue

        connection.execute(
            """
            INSERT INTO pdf_texts (path, mtime_ns, size, content, indexed_at)
            VALUES (?, ?, ?, ?, ?)
            ON CONFLICT(path) DO UPDATE SET
                mtime_ns = excluded.mtime_ns,
                size = excluded.size,
                content = excluded.content,
                indexed_at = excluded.indexed_at
            """,
            (
                relative_path,
                file_stat.st_mtime_ns,
                file_stat.st_size,
                extract_text(pdf_path),
                indexed_at,
            ),
        )

    cached_year_paths = connection.execute(
        "SELECT path FROM pdf_texts WHERE path LIKE ?",
        (f"{arguments.year}/%",),
    ).fetchall()
    for (cached_path,) in cached_year_paths:
        if cached_path not in current_paths:
            connection.execute("DELETE FROM pdf_texts WHERE path = ?", (cached_path,))

    connection.commit()

    matches = [
        path
        for (path,) in connection.execute(
            """
            SELECT path
            FROM pdf_texts
            WHERE path LIKE ? AND instr(content, ?) > 0
            ORDER BY path COLLATE NOCASE
            """,
            (f"{arguments.year}/%", arguments.query),
        )
    ]
    connection.close()

    print(json.dumps(matches, ensure_ascii=False))


if __name__ == "__main__":
    main()
