#!/usr/bin/env node
/**
 * NeNe Serve — W1 stage1 契約名 rename（決定的・再実行可能スクリプト）
 * =====================================================================
 * 規約: `_work/reports/2026-07-14-frontend-standards/` 03 §3(TK-02/TK-06)・05 §9.2 W1 行。
 * 写像は `@hideyukimori/nene2-tokens@1.0.0` の契約語彙（COLOR_KEYS 28 / SHADOW_KEYS 4）と
 * 拡張トークン一般形 `--<cat>-x-<name>`（AM-3）に従う。serve は独自語彙のため、vault が
 * VAULT_TABLE を持つのと同型の「serve 個別表」を本ファイルに versioned で持つ（使い捨て化しない・M-1）。
 *
 * stage1 のスコープ（05 §9.2）: 「契約名 rename・`:root` のまま・値は変えない」。
 *   - `:root` / `[data-theme='dark']` を @theme へ変換しない（stage2/W6）。
 *   - トークンの VALUE は一切変更しない（本スクリプトは NAME だけを置換する）。
 *   - FSD 全面化・theme/ の index.css/active.css/themes 分割はしない（stage2/W6）。
 *
 * 冪等性: 旧名は置換後に消えるため、再実行しても差分ゼロ（再実行可能）。
 *
 * 使い方:
 *   node frontend/scripts/stage1-token-rename.mjs            # 適用
 *   node frontend/scripts/stage1-token-rename.mjs --check    # 取り残し検査のみ（非0で fail）
 */
import { readFileSync, writeFileSync, existsSync, readdirSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import path from 'node:path'

const frontendRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..')

/**
 * serve 個別写像表 v1（old CSS 変数名 → 契約名 / 拡張トークン名）。
 * 分類: 契約 color 14 / 契約 shadow は既に契約名（下の KEEP）/ x- 送り 34。
 * 値（oklch 実値）は不変。x- 送りの個別名は tokens 技術判断（AM-3 brand-violet 前例と同型）。
 */
const MAP = {
  // ── 契約 color（28 キーの語彙へ）──────────────────────────────
  '--bg': '--color-surface', // ページ地
  '--surface': '--color-surface-raised', // カード面（vault と同じ bg=地 / surface=raised の読み）
  '--surface-2': '--color-surface-overlay', // ドロップダウン/モーダル面
  '--surface-sunk': '--color-surface-sunken', // くぼみ面
  '--border': '--color-border',
  '--border-strong': '--color-border-strong',
  '--ink': '--color-text-primary',
  '--ink-2': '--color-text-muted',
  '--ink-3': '--color-text-faint',
  '--ink-on-accent': '--color-on-accent',
  '--accent': '--color-accent',
  '--accent-hover': '--color-accent-hover',
  '--accent-soft': '--color-accent-soft',
  '--ring': '--color-focus-ring',

  // ── x- 送り（契約外の色。serve 固有の意味論を保つ）────────────
  '--hair': '--color-x-hair', // 罫線より薄いヘアライン
  '--accent-line': '--color-x-accent-line',
  '--hi': '--color-x-hi', // clay highlight（第2アクセント）
  '--hi-soft': '--color-x-hi-soft',
  '--charcoal': '--color-x-charcoal', // サイドバー地
  // status 5 族 × 4 部位（ink=chip文字 / soft=chip地 / line=chip罫 / dot=標）。
  // 契約 status（danger/success/warn/info の base+soft+on- の3部位）と構造が異なるため
  // 族ごと x- に保つ（go/stop/pending → success/danger/warn への合流は stage2 で contrast と共に再審）。
  '--st-neutral-ink': '--color-x-st-neutral-ink',
  '--st-neutral-soft': '--color-x-st-neutral-soft',
  '--st-neutral-line': '--color-x-st-neutral-line',
  '--st-neutral-dot': '--color-x-st-neutral-dot',
  '--st-pending-ink': '--color-x-st-pending-ink',
  '--st-pending-soft': '--color-x-st-pending-soft',
  '--st-pending-line': '--color-x-st-pending-line',
  '--st-pending-dot': '--color-x-st-pending-dot',
  '--st-go-ink': '--color-x-st-go-ink',
  '--st-go-soft': '--color-x-st-go-soft',
  '--st-go-line': '--color-x-st-go-line',
  '--st-go-dot': '--color-x-st-go-dot',
  '--st-stop-ink': '--color-x-st-stop-ink',
  '--st-stop-soft': '--color-x-st-stop-soft',
  '--st-stop-line': '--color-x-st-stop-line',
  '--st-stop-dot': '--color-x-st-stop-dot',
  '--st-info-ink': '--color-x-st-info-ink',
  '--st-info-soft': '--color-x-st-info-soft',
  '--st-info-line': '--color-x-st-info-line',
  '--st-info-dot': '--color-x-st-info-dot',
  // 非 color カテゴリ（v1 スコープ外 = typography/radius/spacing。x- 拡張へ）
  '--font-sans': '--font-x-sans',
  '--font-mono': '--font-x-mono',
  '--font-serif': '--font-x-serif',
  '--r-sm': '--radius-x-sm',
  '--r-md': '--radius-x-md',
  '--r-lg': '--radius-x-lg',
  '--r-xl': '--radius-x-xl',
  '--nav-w': '--space-x-nav-w',
  '--topbar-h': '--space-x-topbar-h',
}

/**
 * 触らないもの（明示）:
 *  - `--shadow-sm|md|lg`  … 既に契約 shadow 名（SHADOW_KEYS）。rename 不要。
 *  - component-local vars … `--tone-ink|--tone-soft|--line|--dot|--pw-normal|--pw-wide|--side-on`
 *    はルール内の局所計算変数（トークンではない）。名前は据え置き、参照先は本 MAP が追随する。
 *  - runtime-injected     … `--pct`（HomeView）/ `--embed-scale`（埋め込み）は局所注入。
 *  - `--danger`           … Input/Select が `var(--danger)` を参照するが serve.css に定義なし
 *                           （既存の宙吊り参照 = 既知バグ）。MAP 外につき本スクリプトは触れない。
 */
const KEEP_CONTRACT_SHADOW = ['--shadow-sm', '--shadow-md', '--shadow-lg']

// 対象ファイル: src 配下の全 .css/.ts/.tsx を走査する（トークン参照は CSS だけでなく
// tsx の SVG fill/inline style にも現れる — icons.tsx の var(--accent) 等）。生成物は除外。
function walk(dir) {
  const out = []
  for (const ent of readdirSync(dir, { withFileTypes: true })) {
    const abs = path.join(dir, ent.name)
    if (ent.isDirectory()) {
      out.push(...walk(abs))
    } else if (/\.(css|ts|tsx)$/.test(ent.name) && !/\.gen\.ts$/.test(ent.name)) {
      out.push(path.relative(frontendRoot, abs))
    }
  }
  return out
}
const srcRoot = path.join(frontendRoot, 'src')
if (!existsSync(srcRoot)) {
  console.error('src/ が見つからない。frontend/ 直下で実行しているか確認。')
  process.exit(2)
}
const targets = walk(srcRoot)

// 長い名から先に。境界（前後が [\w-] でない）で厳密一致 → prefix 衝突（--ink vs --ink-2）を防ぐ。
const pairs = Object.entries(MAP).sort((a, b) => b[0].length - a[0].length)
const boundary = (name) => new RegExp(`(?<![\\w-])${name.replace(/[-]/g, '\\-')}(?![\\w-])`, 'g')

const checkOnly = process.argv.includes('--check')
let totalReplacements = 0

for (const rel of targets) {
  const abs = path.join(frontendRoot, rel)
  let text = readFileSync(abs, 'utf8')
  let fileCount = 0
  for (const [oldName, newName] of pairs) {
    const re = boundary(oldName)
    const n = (text.match(re) ?? []).length
    if (n > 0) {
      text = text.replace(re, newName)
      fileCount += n
    }
  }
  if (!checkOnly && fileCount > 0) writeFileSync(abs, text)
  if (fileCount > 0) console.log(`${checkOnly ? '[would] ' : ''}${rel}: ${fileCount} 置換`)
  totalReplacements += fileCount
}

// 取り残し検査: MAP の旧名が残っていないか（--check でも適用後でも 0 であるべき）。
let leftover = 0
for (const rel of targets) {
  const text = readFileSync(path.join(frontendRoot, rel), 'utf8')
  for (const [oldName] of pairs) {
    const n = (text.match(boundary(oldName)) ?? []).length
    if (n > 0) {
      console.error(`取り残し: ${rel} に ${oldName} が ${n} 個残存`)
      leftover += n
    }
  }
}

console.log(`\n置換合計: ${totalReplacements} / 取り残し: ${leftover}`)
console.log(
  `写像: 契約 color 14 + x- 送り 34 = 48 名。据え置き: 契約 shadow ${KEEP_CONTRACT_SHADOW.length} + 局所変数 7。`,
)
if (checkOnly && totalReplacements > 0) {
  console.error('\n--check: 未適用の旧名が残っている（rename が未実行）。')
  process.exit(1)
}
if (leftover > 0) process.exit(1)
