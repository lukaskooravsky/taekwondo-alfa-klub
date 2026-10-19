---
name: reference-webglobe-php-tmp
description: Webglobe hosting (oravsky.sk) má PHP tmp nezapisovateľný — neukladajú sa relácie ani nahrané súbory
metadata:
  node_type: memory
  type: reference
  originSessionId: b3956cdb-161a-4185-8cf7-509308ed727e
  modified: 2026-10-18T21:35:37.578Z
---

Na Webglobe hostingu (oravsky.sk, PHP 8.4 fpm) je `session.save_path` aj `upload_tmp_dir` = `/home/html/oravsky.sk/tmp`,
vlastnený rootom a **nezapisovateľný**. Následky: `session_start()` → Permission denied (formuláre „Platnosť vypršala“),
každý multipart upload → `UPLOAD_ERR_NO_TMP_DIR` (6). `upload_tmp_dir` je PHP_INI_SYSTEM — `.user.ini` ho nezmení.
Riešenia v ALFA webe: vlastný session handler do `data/sessions`, fotky posielať ako base64 v TEXTOVÝCH poliach
multipart (FormData) a zapisovať cez vlastný `data/tmp`. **JSON/urlencoded telo nad ~16 kB PHP zahodí** („POST data can't be
buffered“ — drží v pamäti len 16 kB, zvyšok do temp) — JSON verzia preto 2026-10-19 zlyhávala skoro pri každej fotke.
Dlhé texty vo formulároch tiež len cez multipart. Iná možnosť: požiadať podporu Webglobe o opravu práv tmp.
Diagnostika: dočasný PHP skript s `ini_get('upload_tmp_dir')`, `is_writable(sys_get_temp_dir())`, `$_FILES` error; potom zmazať.
Súvisí s [[project-alfa-taekwondo-web]].
