import { readFileSync, readdirSync } from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { describe, expect, it } from 'vitest'

/**
 * Guard against dangling design-token references (nene-serve issue #156).
 *
 * `var(--not-defined)` is silent: the declaration is simply dropped and the
 * element renders with its inherited value, so an error border or a brand fill
 * can quietly stop applying. #156 sat open for 15 days for exactly that reason —
 * `var(--danger)` and `var(--fg)` had never existed in the token vocabulary, and
 * nothing failed.
 *
 * `serve.css` is the single theme file (design system v3, OKLCH tokens), so
 * "defined" means "declared there". A reference with a fallback —
 * `var(--pct, 0%)` — is intentional: the value is supplied at runtime by an
 * inline style, and the fallback is the contract. Those are allowed.
 */

const here = path.dirname(fileURLToPath(import.meta.url))
const srcRoot = path.resolve(here, '../../../')
const themeFile = path.join(here, 'serve.css')

function sourceFiles(dir: string): string[] {
  return readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
    const full = path.join(dir, entry.name)
    if (entry.isDirectory()) return sourceFiles(full)
    return /\.(css|ts|tsx)$/.test(entry.name) && !/\.test\.tsx?$/.test(entry.name) ? [full] : []
  })
}

/** Custom properties declared in the theme, e.g. `--color-text-primary: …`. */
function declaredTokens(css: string): Set<string> {
  return new Set(Array.from(css.matchAll(/^\s*(--[\w-]+)\s*:/gm), (m) => m[1] as string))
}

/** `var(--x)` references that supply no fallback, so they must resolve. */
function requiredTokens(source: string): string[] {
  return Array.from(source.matchAll(/var\(\s*(--[\w-]+)\s*\)/g), (m) => m[1] as string)
}

describe('serve.css token contract', () => {
  const declared = declaredTokens(readFileSync(themeFile, 'utf8'))

  it('declares tokens at all (guards the parser itself)', () => {
    expect(declared.size).toBeGreaterThan(20)
    expect(declared.has('--color-text-primary')).toBe(true)
  })

  it('has no fallback-less var() reference to an undeclared token', () => {
    const dangling = sourceFiles(srcRoot)
      .flatMap((file) =>
        requiredTokens(readFileSync(file, 'utf8'))
          .filter((token) => !declared.has(token))
          .map((token) => `${path.relative(srcRoot, file)} → var(${token})`),
      )
      .sort()

    expect(dangling).toEqual([])
  })
})
