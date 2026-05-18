import { useEffect, useMemo, useState } from 'react'
import './App.css'

const API_BASE = import.meta.env.VITE_API_BASE || '/api/v1'

function fetchJson(url, options) {
  return fetch(url, { credentials: 'include', ...(options || {}) }).then(async (res) => {
    const data = await res.json().catch(() => ({}))
    if (!res.ok || data?.ok === false) {
      throw new Error(data?.error || `Request failed (${res.status})`)
    }
    return data
  })
}

function formatText(text) {
  if (!text) return '-'
  return text
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
  const [languages, setLanguages] = useState([])
  const [language, setLanguage] = useState('1')
  const [source, setSource] = useState('')
  const [submitLoading, setSubmitLoading] = useState(false)
  const [submitMessage, setSubmitMessage] = useState('')
  const [loadingProblems, setLoadingProblems] = useState(false)
  const [loadingDetail, setLoadingDetail] = useState(false)
  const [loadingStatus, setLoadingStatus] = useState(false)
  const [error, setError] = useState('')

  const pageSize = 20
  const totalPages = useMemo(() => Math.max(1, Math.ceil(problemTotal / pageSize)), [problemTotal])

  const refreshStatus = () => {
    const params = new URLSearchParams({ page: '1', page_size: '12' })
    if (selectedId) params.set('problem_id', String(selectedId))

    setLoadingStatus(true)
    fetchJson(`${API_BASE}/status.php?${params.toString()}`)
      .then((data) => setStatusItems(data.items || []))
      .catch((e) => setError(e.message))
      .finally(() => setLoadingStatus(false))
  }

  useEffect(() => {
    let active = true
    setLoadingProblems(true)
    setError('')
    const params = new URLSearchParams({ page: String(page), page_size: String(pageSize) })
    if (query) params.set('search', query)

    fetchJson(`${API_BASE}/problems.php?${params.toString()}`)
      .then((data) => {
        if (!active) return
        const items = data.items || []
        setProblems(items)
        setProblemTotal(data.total || 0)

        if (!items.length) {
          setSelectedId(null)
          setProblemDetail(null)
          return
        }

        const exists = selectedId && items.some((p) => p.problem_id === selectedId)
        if (!exists) setSelectedId(items[0].problem_id)
      })
      .catch((e) => active && setError(e.message))
      .finally(() => active && setLoadingProblems(false))

    return () => {
      active = false
    }
  }, [page, query, selectedId])

  useEffect(() => {
    let active = true
    fetchJson(`${API_BASE}/languages.php`)
      .then((data) => {
        if (!active) return
        const enabled = (data.items || []).filter((l) => l.enabled)
        setLanguages(enabled)
        if (enabled.length > 0) {
          setLanguage(String(enabled[0].id))
        }
      })
      .catch((e) => active && setError(e.message))

    return () => {
      active = false
    }
  }, [])

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
    refreshStatus()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selectedId])

  function onSearchSubmit(e) {
    e.preventDefault()
    setPage(1)
    setQuery(search.trim())
  }

  function onSubmitCode(e) {
    e.preventDefault()
    if (!selectedId) return
    setSubmitLoading(true)
    setSubmitMessage('')
    setError('')

    fetchJson(`${API_BASE}/submit.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        problem_id: selectedId,
        language: Number(language),
        source,
      }),
    })
      .then((data) => {
        setSubmitMessage(`제출 완료: #${data.solution_id}`)
        refreshStatus()
      })
      .catch((e) => setError(e.message))
      .finally(() => setSubmitLoading(false))
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
      {submitMessage ? <div className="success">{submitMessage}</div> : null}

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
                <pre className="contentBlock">{formatText(problemDetail.description)}</pre>
              </section>
              <section>
                <h4>Input</h4>
                <pre className="contentBlock">{formatText(problemDetail.input)}</pre>
              </section>
              <section>
                <h4>Output</h4>
                <pre className="contentBlock">{formatText(problemDetail.output)}</pre>
              </section>
              <section>
                <h4>Sample Input</h4>
                <pre className="contentBlock">{formatText(problemDetail.sample_input)}</pre>
              </section>
              <section>
                <h4>Sample Output</h4>
                <pre className="contentBlock">{formatText(problemDetail.sample_output)}</pre>
              </section>
            </div>
          ) : (
            <p>문제를 선택하세요.</p>
          )}
        </article>

        <aside className="panel">
          <h2>코드 제출</h2>
          <form className="submitForm" onSubmit={onSubmitCode}>
            <label>
              언어
              <select value={language} onChange={(e) => setLanguage(e.target.value)}>
                {languages.map((lang) => (
                  <option key={lang.id} value={lang.id}>{lang.name}</option>
                ))}
              </select>
            </label>
            <label>
              소스코드
              <textarea
                value={source}
                onChange={(e) => setSource(e.target.value)}
                placeholder="코드를 입력하세요"
                rows={12}
              />
            </label>
            <button type="submit" disabled={submitLoading || !selectedId || !source.trim()}>
              {submitLoading ? '제출 중...' : '제출'}
            </button>
          </form>

          <h2 className="statusTitle">최근 제출</h2>
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
