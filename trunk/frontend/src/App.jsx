import { useEffect, useMemo, useState } from 'react'
import './App.css'

const API_BASE = import.meta.env.VITE_API_BASE || '/api/v1'

function fetchJson(url) {
  return fetch(url, { credentials: 'include' }).then(async (res) => {
    const data = await res.json().catch(() => ({}))
    if (!res.ok || data?.ok === false) {
      throw new Error(data?.error || `Request failed (${res.status})`)
    }
    return data
  })
}

function App() {
  const [search, setSearch] = useState('')
  const [query, setQuery] = useState('')
  const [page, setPage] = useState(1)
  const [problems, setProblems] = useState([])
  const [problemTotal, setProblemTotal] = useState(0)
  const [selectedId, setSelectedId] = useState(null)
  const [problemDetail, setProblemDetail] = useState(null)
  const [statusItems, setStatusItems] = useState([])
  const [loadingProblems, setLoadingProblems] = useState(false)
  const [loadingDetail, setLoadingDetail] = useState(false)
  const [loadingStatus, setLoadingStatus] = useState(false)
  const [error, setError] = useState('')

  const pageSize = 20
  const totalPages = useMemo(() => Math.max(1, Math.ceil(problemTotal / pageSize)), [problemTotal])

  useEffect(() => {
    let active = true
    setLoadingProblems(true)
    setError('')
    const params = new URLSearchParams({ page: String(page), page_size: String(pageSize) })
    if (query) params.set('search', query)

    fetchJson(`${API_BASE}/problems.php?${params.toString()}`)
      .then((data) => {
        if (!active) return
        setProblems(data.items || [])
        setProblemTotal(data.total || 0)
        if (!selectedId && data.items?.length) {
          setSelectedId(data.items[0].problem_id)
        }
      })
      .catch((e) => active && setError(e.message))
      .finally(() => active && setLoadingProblems(false))

    return () => {
      active = false
    }
  }, [page, query, selectedId])

  useEffect(() => {
    if (!selectedId) return
    let active = true
    setLoadingDetail(true)
    setError('')

    fetchJson(`${API_BASE}/problem.php?id=${selectedId}`)
      .then((data) => {
        if (!active) return
        setProblemDetail(data.item)
      })
      .catch((e) => active && setError(e.message))
      .finally(() => active && setLoadingDetail(false))

    return () => {
      active = false
    }
  }, [selectedId])

  useEffect(() => {
    let active = true
    setLoadingStatus(true)
    setError('')

    const params = new URLSearchParams({ page: '1', page_size: '12' })
    if (selectedId) params.set('problem_id', String(selectedId))

    fetchJson(`${API_BASE}/status.php?${params.toString()}`)
      .then((data) => {
        if (!active) return
        setStatusItems(data.items || [])
      })
      .catch((e) => active && setError(e.message))
      .finally(() => active && setLoadingStatus(false))

    return () => {
      active = false
    }
  }, [selectedId])

  function onSearchSubmit(e) {
    e.preventDefault()
    setPage(1)
    setQuery(search.trim())
  }

  return (
    <main className="app">
      <header className="header">
        <h1>BackOJ React Frontend</h1>
        <form onSubmit={onSearchSubmit} className="searchForm">
          <input
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="문제 제목/출처 검색"
            aria-label="문제 검색"
          />
          <button type="submit">검색</button>
        </form>
      </header>

      {error ? <div className="error">{error}</div> : null}

      <section className="layout">
        <aside className="panel">
          <h2>문제 목록</h2>
          {loadingProblems ? <p>Loading...</p> : null}
          <ul className="problemList">
            {problems.map((p) => (
              <li key={p.problem_id}>
                <button
                  className={p.problem_id === selectedId ? 'active' : ''}
                  onClick={() => setSelectedId(p.problem_id)}
                  type="button"
                >
                  <span>#{p.problem_id}</span>
                  <strong>{p.title}</strong>
                  <small>AC {p.accepted} / SUB {p.submit}</small>
                </button>
              </li>
            ))}
          </ul>
          <div className="pager">
            <button onClick={() => setPage((v) => Math.max(1, v - 1))} disabled={page <= 1}>이전</button>
            <span>{page} / {totalPages}</span>
            <button onClick={() => setPage((v) => Math.min(totalPages, v + 1))} disabled={page >= totalPages}>다음</button>
          </div>
        </aside>

        <article className="panel">
          <h2>문제 상세</h2>
          {loadingDetail ? <p>Loading...</p> : null}
          {problemDetail ? (
            <div className="detail">
              <h3>#{problemDetail.problem_id} {problemDetail.title}</h3>
              <div className="meta">시간 제한: {problemDetail.time_limit} ms / 메모리 제한: {problemDetail.memory_limit} KB</div>
              <section>
                <h4>Description</h4>
                <div dangerouslySetInnerHTML={{ __html: problemDetail.description || '' }} />
              </section>
              <section>
                <h4>Input</h4>
                <div dangerouslySetInnerHTML={{ __html: problemDetail.input || '' }} />
              </section>
              <section>
                <h4>Output</h4>
                <div dangerouslySetInnerHTML={{ __html: problemDetail.output || '' }} />
              </section>
            </div>
          ) : (
            <p>문제를 선택하세요.</p>
          )}
        </article>

        <aside className="panel">
          <h2>최근 제출</h2>
          {loadingStatus ? <p>Loading...</p> : null}
          <ul className="statusList">
            {statusItems.map((s) => (
              <li key={s.solution_id}>
                <div>
                  <strong>#{s.solution_id}</strong> / P{s.problem_id}
                </div>
                <div>{s.user_id} · {s.language_text}</div>
                <div>{s.result_text} · {s.time} ms · {s.memory} KB</div>
              </li>
            ))}
          </ul>
        </aside>
      </section>
    </main>
  )
}

export default App
