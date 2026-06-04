/*!
 * NeNe Serve — serve.js embed client (binding contract: docs/explanation/serve-embed-spec.md).
 *
 * Dependency-free, CSP-friendly single entry point. No eval of API responses.
 * Publishers embed:
 *   <script src="https://{serve-host}/serve.js" data-placement="{key}" async></script>
 *
 * The script is intentionally defensive: any failure renders nothing and never
 * throws into the host page. Public endpoints are origin-gated, rate-limited, and
 * return opaque tokens only (api-security-spec.md, ADR 0018/0019).
 */
(function () {
  'use strict'

  var PROCESSED = 'neneInit'

  /** Origin that served this script (the API base), derived from the <script src>. */
  function apiBase(script) {
    try {
      return new URL(script.src).origin
    } catch (e) {
      return ''
    }
  }

  function consentState(script) {
    var c = script.getAttribute('data-consent') || window.NeneServeConsent
    return c === 'granted' ? 'granted' : 'unknown'
  }

  function el(tag, attrs) {
    var node = document.createElement(tag)
    for (var key in attrs) {
      if (Object.prototype.hasOwnProperty.call(attrs, key) && attrs[key] != null) {
        node.setAttribute(key, String(attrs[key]))
      }
    }
    return node
  }

  function clickAnchor(child, href) {
    var a = el('a', { href: href, target: '_blank', rel: 'noopener noreferrer' })
    a.style.display = 'block'
    a.appendChild(child)
    return a
  }

  /** Fire the impression beacon once (idempotent server-side; replay never inflates). */
  function beaconImpression(base, token, consent) {
    if (!token) return
    var url = base + '/public/events/impression'
    var body = JSON.stringify({ impression_token: token, consent_state: consent })
    try {
      if (navigator && typeof navigator.sendBeacon === 'function') {
        navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }))
        return
      }
    } catch (e) {
      /* fall through to fetch */
    }
    try {
      fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: body,
        keepalive: true,
        mode: 'cors',
        credentials: 'omit',
      }).catch(function () {})
    } catch (e) {
      /* ignore */
    }
  }

  /** Run cb when the node is at least 50% visible (viewable impression), else now. */
  function whenViewable(node, cb) {
    if (typeof IntersectionObserver === 'undefined') {
      cb()
      return
    }
    var io = new IntersectionObserver(
      function (entries) {
        for (var i = 0; i < entries.length; i++) {
          if (entries[i].isIntersecting) {
            io.disconnect()
            cb()
            return
          }
        }
      },
      { threshold: 0.5 },
    )
    io.observe(node)
  }

  function render(slot, data, base, consent) {
    var c = data.creative || {}
    var clickUrl = data.click_url ? base + data.click_url : null
    var node = null

    if (c.type === 'image') {
      var img = el('img', { src: c.asset_url, width: c.width, height: c.height, alt: '', loading: 'lazy' })
      img.style.display = 'block'
      img.style.border = '0'
      node = clickUrl ? clickAnchor(img, clickUrl) : img
    } else if (c.type === 'video') {
      var video = el('video', {
        width: c.width,
        height: c.height,
        poster: c.poster_url,
        preload: 'metadata',
        playsinline: '',
        controls: '',
      })
      video.muted = true
      video.autoplay = false
      video.appendChild(el('source', { src: c.asset_url }))
      node = clickUrl ? clickAnchor(video, clickUrl) : video
    } else if (c.type === 'html5_bundle' && c.render && c.render.frame_url) {
      // Approved bundle only, rendered in a strict sandbox (no allow-same-origin,
      // no top navigation). The bundle handles its own clicks inside the frame.
      node = el('iframe', {
        src: base + c.render.frame_url,
        width: c.width,
        height: c.height,
        sandbox: c.render.sandbox || 'allow-scripts',
        referrerpolicy: 'no-referrer',
        loading: 'lazy',
        scrolling: 'no',
        title: 'advertisement',
      })
      node.style.border = '0'
    } else {
      return // unknown creative type — render nothing
    }

    slot.appendChild(node)
    whenViewable(slot, function () {
      beaconImpression(base, data.impression_token, consent)
    })
  }

  function process(script) {
    if (script.dataset[PROCESSED]) return
    script.dataset[PROCESSED] = '1'

    var key = script.getAttribute('data-placement')
    if (!key) return
    var base = apiBase(script)
    if (!base) return
    var consent = consentState(script)

    var slot = el('div', { 'data-nene-slot': key })
    slot.style.display = 'block'
    // Insert the ad slot immediately after the embedding <script>.
    if (script.parentNode) {
      script.parentNode.insertBefore(slot, script.nextSibling)
    }

    var url = base + '/public/placements/' + encodeURIComponent(key) + '/serve?consent=' + consent

    fetch(url, { method: 'GET', mode: 'cors', credentials: 'omit' })
      .then(function (response) {
        if (response.status === 204 || !response.ok) return null // empty serve / error → nothing
        return response.json()
      })
      .then(function (data) {
        if (data) render(slot, data, base, consent)
      })
      .catch(function () {
        /* never break the host page */
      })
  }

  function init() {
    var scripts = document.querySelectorAll('script[data-placement]')
    for (var i = 0; i < scripts.length; i++) {
      process(scripts[i])
    }
  }

  init()
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init)
  }
})()
