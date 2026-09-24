---
name: deploy-git-lukasko-token
description: "Ako pushnúť ALFA Taekwondo web — repo patrí lukaskooravsky, lokálny gh je suspendovaný DusanOravsky"
metadata: 
  node_type: memory
  type: reference
  originSessionId: 5ec1f520-6c77-43e6-9002-0d521bb3d45e
  modified: 2026-09-16T17:41:09.603Z
---

Repozitár `lukaskooravsky/taekwondo-alfa-klub` patrí účtu **lukaskooravsky**.
Na tomto stroji je `gh` prihlásené ako **DusanOravsky** (to je používateľ), ktorý má na repe len **READ** a jeho **SSH kľúč je pozastavený** („Your account is suspended"). Preto push cez SSH ani cez DusanOravsky **nefunguje**.

**Push ide len ako lukaskooravsky cez HTTPS token** (classic token, scope `repo`, z https://github.com/settings/tokens). Token NEukladať do configu — použiť ho priamo v URL:

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
