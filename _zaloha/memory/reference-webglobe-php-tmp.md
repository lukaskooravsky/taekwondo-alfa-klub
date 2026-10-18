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
Riešenia v ALFA webe: vlastný session handler do `data/sessions`, fotky posielať ako JSON/base64 (telo < 2 MB, inak PHP
aj tak potrebuje temp súbor) a zapisovať cez vlastný `data/tmp`. Iná možnosť: požiadať podporu Webglobe o opravu práv tmp.
Diagnostika: dočasný PHP skript s `ini_get('upload_tmp_dir')`, `is_writable(sys_get_temp_dir())`, `$_FILES` error; potom zmazať.
Súvisí s [[project-alfa-taekwondo-web]].
