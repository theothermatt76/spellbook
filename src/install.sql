BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "spells" (
	"id"	INTEGER,
	"tool"	TEXT,
	"tag"	TEXT,
	"description"	TEXT,
	"cli"	TEXT,
	PRIMARY KEY("id" AUTOINCREMENT)
);
INSERT INTO "spells" ("id","tool","tag","description","cli") VALUES (1,'sample_tool','sample','this is just to populate the db, remove later','history | less');
INSERT INTO "spells" ("id","tool","tag","description","cli") VALUES (2,'sample2','sample','again, just population','nc -nlvp 8998');
COMMIT;
