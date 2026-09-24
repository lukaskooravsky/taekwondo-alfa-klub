---
name: deploy-git-lukasko-token
description: "Ako pushnúť ALFA Taekwondo web — repo patrí lukaskooravsky, lokálny gh je suspendovaný DusanOravsky"
metadata: 
  node_type: memory
  type: reference
  originSessionId: 5ec1f520-6c77-43e6-9002-0d521bb3d45e
  modified: 2026-09-24T14:12:42.649Z
---

Repozitár `lukaskooravsky/taekwondo-alfa-klub` patrí účtu **lukaskooravsky** a je **verejný** (kvôli GitHub Pages).

**AKTUÁLNY funkčný spôsob (od 2026-09-24):** `gh` je teraz prihlásené aj ako **lukaskooravsky** cez OAuth (browser device flow), je to aktívny účet a nastavený git credential helper. Push preto funguje jednoducho:
```
gh auth login -h github.com -w      # ak treba znova (starší gh nepozná -p/--git-protocol)
gh auth setup-git -h github.com
cd .../site && git push origin HEAD:main
```
Tento gh verzia `gh auth login` v neinteraktívnom `!` režime **vyžaduje `-w` (--web)** — inak padne s „--web or --with-token required".

---
**Staršia poznámka (fallback cez token):** pôvodne bolo `gh` len ako DusanOravsky (READ, suspendovaný SSH), takže push išiel len ako lukaskooravsky cez HTTPS token (classic, scope `repo`) priamo v URL:

```
git -c credential.helper= push \
  "https://lukaskooravsky:<TOKEN>@github.com/lukaskooravsky/taekwondo-alfa-klub.git" \
  main:main
```

Pozor: `git push -u` s tokenom v URL uloží token do `.git/config` (`branch.main.remote`). Po pushi to treba prepnúť späť: `git config branch.main.remote origin; git config branch.main.merge refs/heads/main` a overiť `grep ghp_ .git/config`. Remote `origin` drž čistý bez tokenu.

Používateľ token po pushi zvyčajne **revokuje** (poslal ho do chatu), takže na ďalší push si vyžiada nový. `gh auth login --with-token` s classic tokenom padá na chýbajúci scope `read:org` — preto obísť gh a pushovať priamo git-om.

GitHub Pages sa dá zapnúť cez API tým istým tokenom:
`POST /repos/lukaskooravsky/taekwondo-alfa-klub/pages` s `{"source":{"branch":"main","path":"/"}}`.

Súvisí s [[project-alfa-taekwondo-web]].
