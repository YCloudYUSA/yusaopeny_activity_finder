import { createApp } from 'vue'

import App from '@/App.vue'
import { library } from '@fortawesome/fontawesome-svg-core'
import {
  faFilter,
  faCalendar,
  faMoneyBill,
  faClock,
  faChevronDown,
  faChevronUp,
  faBookmark,
  faPlusCircle,
  faMinusCircle,
  faSortAmountDown
} from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

library.add([
  faFilter,
  faCalendar,
  faMoneyBill,
  faClock,
  faChevronDown,
  faChevronUp,
  faBookmark,
  faPlusCircle,
  faMinusCircle,
  faSortAmountDown
])

// Listen to custom event to track events in Google Analytics.
document.addEventListener('openy_activity_finder_event', e => {
  const { action, label, value, category } = e.detail

  if (window.gtag) {
    window.gtag('event', action, {
      event_category: category,
      event_label: label,
      value: value
    })
  } else if (window.ga) {
    window.ga('send', 'event', category, action, label, value)
  }
})

const app = createApp({
  components: { 'activity-finder': App }
})

app.component('font-awesome-icon', FontAwesomeIcon)

// Global helper methods — available as this.t(), t() in templates via globalProperties.
app.config.globalProperties.t = function(value, args, options = { context: 'Activity Finder' }) {
  return window.Drupal.t(value, args, options)
}
app.config.globalProperties.formatPlural = function(value, singular, plural, args, options = { context: 'Activity Finder' }) {
  if (!value) return ''
  return window.Drupal.formatPlural(value, singular, plural, args, options)
}
app.config.globalProperties.capitalize = function(str) {
  if (!str) return ''
  str = str.toString()
  return str[0].toUpperCase() + str.slice(1)
}
app.config.globalProperties.trackEvent = function(action, label, value = 0, category = 'Activity Finder') {
  const event = new CustomEvent('openy_activity_finder_event', {
    detail: { action, label, value, category }
  })
  document.dispatchEvent(event)
}
app.config.globalProperties.getCookie = function(cname) {
  const name = cname + '='
  const decodedCookie = decodeURIComponent(document.cookie)
  const ca = decodedCookie.split(';')
  for (let i = 0; i < ca.length; i++) {
    let c = ca[i]
    while (c[0] === ' ') c = c.slice(1)
    if (c.startsWith(name)) return c.slice(name.length, c.length)
  }
  return ''
}
app.config.globalProperties.isIosMobile = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream

app.mount('#activity-finder')
