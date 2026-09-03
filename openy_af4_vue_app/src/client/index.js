const DEFAULT = 'af/get-data'
const SESSION_DATA = 'af/api/v1/session-data'
const MORE_INFO = 'af/more-info'

// Serialize params like axios does: arrays → key[]=val, scalars → key=val.
function toQueryString(params) {
  const parts = []
  for (const [key, value] of Object.entries(params)) {
    if (Array.isArray(value)) {
      value.forEach(v => parts.push(`${encodeURIComponent(key)}[]=${encodeURIComponent(v)}`))
    } else if (value !== undefined && value !== null) {
      parts.push(`${encodeURIComponent(key)}=${encodeURIComponent(value)}`)
    }
  }
  return parts.join('&')
}

const client = flag => {
  let path = ''
  switch (flag) {
    case 'session_data':
      path = SESSION_DATA
      break
    case 'more_info':
      path = MORE_INFO
      break
    default:
      path = DEFAULT
  }

  const baseURL = window.drupalSettings.path.baseUrl + path

  return {
    request({ params = {} } = {}) {
      const query = toQueryString(params)
      const url = query ? `${baseURL}?${query}` : baseURL
      return fetch(url)
        .then(res => res.json())
        .then(data => ({ data }))
    }
  }
}

export default client
