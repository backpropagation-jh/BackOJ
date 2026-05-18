# BackOJ React Frontend (Vite)

## Development

```bash
cd /home/runner/work/BackOJ/BackOJ/trunk/frontend
npm install
npm run dev
```

- Default API base: `/api/v1`
- You can set backend proxy target with `VITE_BACKEND_URL`

## API Endpoints used by frontend

- `GET /api/v1/problems.php`
- `GET /api/v1/problem.php?id=<problem_id>`
- `GET /api/v1/status.php`
- `GET /api/v1/languages.php`
- `POST /api/v1/submit.php`

Submit payload example:

```json
{
  "problem_id": 1000,
  "language": 1,
  "source": "#include <bits/stdc++.h>\nint main(){return 0;}"
}
```

## Build

```bash
cd /home/runner/work/BackOJ/BackOJ/trunk/frontend
npm run build
```

Build output is generated to:

- `/home/runner/work/BackOJ/BackOJ/trunk/web/react/dist`

The PHP entrypoint is:

- `/home/runner/work/BackOJ/BackOJ/trunk/web/react/index.php`
