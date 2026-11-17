import { createPortal, render, useEffect, useMemo, useState } from '@wordpress/element';
import './styles.css';

const STATUS_COLORS = {
	finished: '#16c784',
	failed: '#ef4444',
	waiting: '#f59e0b',
	refunded: '#8b5cf6',
};

/* 
* BTC orange, ETH periwinkle, USDT emerald, SOL purple, USDC blue, LTC slate — match dashboard reference
*/
const COIN_COLORS = ['#f7931a', '#93c5fd', '#10b981', '#9945ff', '#3b82f6', '#64748b', '#94a3b8'];

const COIN_ORDER = ['BTC', 'ETH', 'USDT', 'SOL', 'USDC', 'LTC'];

const COIN_DISPLAY = {
	BTC: 'Bitcoin · BTC',
	ETH: 'Ethereum · ETH',
	USDT: 'Tether · USDT',
	SOL: 'Solana · SOL',
	USDC: 'USD Coin · USDC',
	LTC: 'Litecoin · LTC',
};

function coinColorForSymbol(symbol) {
	const idx = COIN_ORDER.indexOf(String(symbol || '').toUpperCase());
	return COIN_COLORS[idx >= 0 ? idx : 0];
}

function formatAmount(value, currency = 'USD') {
	try {
		return new Intl.NumberFormat(undefined, { style: 'currency', currency }).format(Number(value || 0));
	} catch {
		return `${Number(value || 0).toFixed(2)} ${currency}`;
	}
}

/** 
* Pro upgrade highlights
*/
const PRO_UPGRADE_FEATURES = [
	'Attractive crypto icons on Shop & Product pages',
	'Crypto pricing on Shop & Product pages',
	'WooCommerce Subscriptions Support',
	'HPOS & block-based checkout compatible',
	'Dashboard Insights',
];

function ProUpgradeModal({ upgradeUrl, description, onClose, theme = 'dark' }) {
	useEffect(() => {
		const onKey = (e) => {
			if (e.key === 'Escape') {
				onClose();
			}
		};
		document.addEventListener('keydown', onKey);
		const prev = document.body.style.overflow;
		document.body.style.overflow = 'hidden';
		return () => {
			document.removeEventListener('keydown', onKey);
			document.body.style.overflow = prev;
		};
	}, [onClose]);

	const overlayClass =
		theme === 'dark' ? 'npwc-pro-popup-overlay npwc-pro-popup--dashboard-dark' : 'npwc-pro-popup-overlay';

	return createPortal(
		<div className={overlayClass} role="presentation" style={{ display: 'flex' }} onClick={onClose}>
			<div
				className="npwc-pro-popup-content"
				role="dialog"
				aria-modal="true"
				aria-labelledby="npwc-pro-popup-heading"
				onClick={(e) => e.stopPropagation()}
			>
				<button type="button" className="npwc-pro-popup-close" onClick={onClose} aria-label="Close dialog">
					<span className="dashicons dashicons-no-alt" aria-hidden="true" />
				</button>
				<div className="npwc-pro-popup-inner">
					<div className="npwc-pro-popup-rocket" aria-hidden="true">
						🚀
					</div>
					<p className="npwc-pro-popup-eyebrow">NOWPayments Pro</p>
					<h2 id="npwc-pro-popup-heading">Unlock Your Pro Access</h2>
					{description ? (
						<p>{description}</p>
					) : (
						<p>Upgrade for advanced checkout features, icons, subscriptions, and priority support.</p>
					)}
					<ul className="npwc-pro-popup-features">
						{PRO_UPGRADE_FEATURES.map((line) => (
							<li key={line}>{line}</li>
						))}
					</ul>
					<p className="npwc-pro-popup-offer">Limited-time intro pricing</p>
					<div className="npwc-pro-popup-actions">
						<a href={upgradeUrl} target="_blank" rel="noopener noreferrer" className="npwc-pro-popup-upgrade npwc-pro-upgrade-link">
							Upgrade to Pro
						</a>
						<button type="button" className="npwc-pro-popup-dismiss" onClick={onClose}>
							No thanks, maybe later
						</button>
					</div>
					<div className="npwc-divider" />
					<div className="npwc-trust-badges">
						<div className="npwc-trust-badge">
							<div className="npwc-trust-icon-wrapper">
								<span className="npwc-trust-icon dashicons dashicons-admin-site" aria-hidden="true" />
							</div>
							<div className="npwc-trust-text">
								<div className="npwc-trust-text-primary">Trusted by</div>
								<div className="npwc-trust-text-secondary">3K+ website owners</div>
							</div>
						</div>
						<div className="npwc-trust-badge">
							<div className="npwc-trust-icon-wrapper">
								<span className="npwc-trust-icon dashicons dashicons-star-filled" aria-hidden="true" />
							</div>
							<div className="npwc-trust-text">
								<div className="npwc-trust-text-primary">Rated 4.3/5</div>
								<div className="npwc-trust-text-secondary">by customers</div>
							</div>
						</div>
						<div className="npwc-trust-badge">
							<div className="npwc-trust-icon-wrapper">
								<span className="npwc-trust-icon dashicons dashicons-shield" aria-hidden="true" />
							</div>
							<div className="npwc-trust-text">
								<div className="npwc-trust-text-primary">14-day</div>
								<div className="npwc-trust-text-secondary">money-back guarantee</div>
							</div>
						</div>
					</div>
					<p className="npwc-footer-text">Thank you for choosing NOWPayments!</p>
				</div>
			</div>
		</div>,
		document.body
	);
}

const NPWC_API_BANNER_DISMISS_KEY = 'npwc_dashboard_dismiss_api_banner';

/** 
* If URL contains `npwc_show_api_banner=1`, clear dismiss flag and strip the param (so the notice shows again).
*/
function npwcApiBannerInitialDismissed() {
	if (typeof window === 'undefined') {
		return false;
	}
	try {
		const params = new URLSearchParams(window.location.search);
		if (params.get('npwc_show_api_banner') === '1') {
			window.localStorage.removeItem(NPWC_API_BANNER_DISMISS_KEY);
			params.delete('npwc_show_api_banner');
			const qs = params.toString();
			const nextUrl = window.location.pathname + (qs ? `?${qs}` : '') + (window.location.hash || '');
			window.history.replaceState(null, '', nextUrl);
			return false;
		}
	} catch (e) {
		// ignore
	}
	if (!window.localStorage) {
		return false;
	}
	return window.localStorage.getItem(NPWC_API_BANNER_DISMISS_KEY) === '1';
}

function ApiConfigBanner({ apiConfigured, settingsUrl, docsUrl }) {
	const configured = Number(apiConfigured) === 1;
	const [dismissed, setDismissed] = useState(npwcApiBannerInitialDismissed);

	const onDismiss = () => {
		try {
			window.localStorage.setItem(NPWC_API_BANNER_DISMISS_KEY, '1');
		} catch (e) {
			// Private mode or blocked storage — hide for this session only.
		}
		setDismissed(true);
	};

	if (configured || dismissed) {
		return null;
	}

	const payUrl = settingsUrl || '#';
	const guideUrl = docsUrl || '#';

	return (
		<div className="npwc-api-banner" role="alert">
			<button type="button" className="npwc-api-banner-dismiss" onClick={onDismiss} aria-label="Dismiss API notice">
				×
			</button>
			<div className="npwc-api-banner-inner">
				<div className="npwc-api-banner-text">
					<strong>Add your NOWPayments API key</strong>
					<span>How to set up: copy your API key from the NOWPayments dashboard, then paste it into the gateway settings.</span>
					<span className="npwc-api-banner-docs">
						<a href={guideUrl} target="_blank" rel="noopener noreferrer" aria-label="Setup documentation (opens in a new tab)">
							Setup documentation
						</a>
					</span>
				</div>
				<a
					href={payUrl}
					className="npwc-api-banner-btn"
					target="_blank"
					rel="noopener noreferrer"
					aria-label="Open payment settings (opens in a new tab)"
				>
					Open payment settings
				</a>
			</div>
		</div>
	);
}

function ProLock({ children, upgradeUrl, className = '', modalDescription, theme = 'dark' }) {
	const [open, setOpen] = useState(false);
	const desc = modalDescription || 'See full status distribution, filters & exports.';

	return (
		<div className={`npwc-pro-lock ${className}`.trim()}>
			<div className="npwc-pro-lock-inner">{children}</div>
			<button type="button" className="npwc-pro-lock-hit" onClick={() => setOpen(true)} aria-label="Unlock with Pro — open upgrade details" />
			<span className="pro-badge" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
					<path d="M2 18h20l-2-9-5 4-3-7-3 7-5-4-2 9z" />
					<path d="M5 22h14" />
				</svg>
				PRO
			</span>
			{open ? <ProUpgradeModal upgradeUrl={upgradeUrl} description={desc} theme={theme} onClose={() => setOpen(false)} /> : null}
		</div>
	);
}

function DashboardCard({ label, value, sub }) {
	const ICONS = {
		'Total Crypto Orders': { icon: 'cube', tone: 'purple', trend: '12.4%' },
		'Successful Payments': { icon: 'check', tone: 'green', trend: '8.1%' },
		'Failed Payments': { icon: 'close', tone: 'red', trend: '3.2%' },
		Refunded: { icon: 'refresh', tone: 'green', trend: '0.0%' },
		'Crypto Revenue': { icon: 'wallet', tone: 'violet', trend: '14.7%' },
	};
	const cardMeta = ICONS[label] || { icon: 'cube', tone: 'green', trend: '0.0%' };
	const renderCardIcon = (name) => {
		switch (name) {
			case 'cube':
				return (
					<>
						<path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z" />
						<path d="M12 22V12" />
						<polyline points="3.29 7 12 12 20.71 7" />
						<path d="m7.5 4.27 9 5.15" />
					</>
				);
			case 'check':
				return (
					<>
						<circle cx="12" cy="12" r="10" />
						<path d="m9 12 2 2 4-4" />
					</>
				);
			case 'close':
				return (
					<>
						<circle cx="12" cy="12" r="10" />
						<path d="m15 9-6 6" />
						<path d="m9 9 6 6" />
					</>
				);
			case 'refresh':
				return (
					<>
						<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
						<path d="M21 3v5h-5" />
						<path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
						<path d="M8 16H3v5" />
					</>
				);
			case 'wallet':
				return (
					<>
						<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1" />
						<path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4" />
					</>
				);
			default:
				return null;
		}
	};

	return (
		<div className="npwc-card">
			<div className="npwc-card-head">
				<span className={`npwc-card-icon npwc-icon-${cardMeta.tone}`}>
					<svg viewBox="0 0 24 24" aria-hidden="true">
						{renderCardIcon(cardMeta.icon)}
					</svg>
				</span>
				<span className={`npwc-card-trend npwc-trend-${cardMeta.tone}`}>+{cardMeta.trend}</span>
			</div>
			<div className="npwc-card-label">{label}</div>
			<div className="npwc-card-value">{value}</div>
			<div className="npwc-card-sub">{sub}</div>
		</div>
	);
}

function LineChart({ points, labels }) {
	const maxPoint = Math.max(1, ...points);
	const yTop = Math.ceil(maxPoint / 2500) * 2500;
	// Fractions from the top of the plot (0 = top, 1 = bottom).
	const yTicks = [1, 0.75, 0.5, 0.25, 0].map((ratio, idx) => ({ value: Math.round(yTop * ratio), offset: idx / 4 }));
	const lastIndex = Math.max(1, points.length - 1);
	// The SVG only draws lines in a 100×100 box stretched to the plot; text lives in HTML so it never distorts.
	const chartPoints = points.map((point, index) => `${(index / lastIndex) * 100},${(1 - point / yTop) * 100}`);
	const areaPoints = ['0,100', ...chartPoints, '100,100'].join(' ');

	return (
		<div className="npwc-chart-wrap">
			<div className="npwc-chart-body">
				<div className="npwc-chart-y" aria-hidden="true">
					{yTicks.map((tick) => (
						<span key={tick.offset} style={{ top: `${tick.offset * 100}%` }}>
							{tick.value}
						</span>
					))}
				</div>
				<div className="npwc-chart-plot">
					<svg viewBox="0 0 100 100" className="npwc-line-chart" preserveAspectRatio="none" aria-hidden="true">
						<defs>
							<linearGradient id="npwcRevenueGradient" x1="0" y1="0" x2="0" y2="1">
								<stop offset="0%" stopColor="#1fd69a" stopOpacity="0.45" />
								<stop offset="100%" stopColor="#1fd69a" stopOpacity="0.05" />
							</linearGradient>
						</defs>
						{yTicks.map((tick) => (
							<line
								key={tick.offset}
								x1="0"
								y1={tick.offset * 100}
								x2="100"
								y2={tick.offset * 100}
								className="npwc-chart-gridline"
								vectorEffect="non-scaling-stroke"
							/>
						))}
						<polygon points={areaPoints} fill="url(#npwcRevenueGradient)" />
						<polyline points={chartPoints.join(' ')} fill="none" stroke="#1fd69a" strokeWidth="3" vectorEffect="non-scaling-stroke" />
					</svg>
				</div>
			</div>
			<div className="npwc-axis">
				{labels.map((label, index) => (
					<span key={label} style={{ left: `${(index / lastIndex) * 100}%` }}>
						{label}
					</span>
				))}
			</div>
		</div>
	);
}

function SectionIcon({ name }) {
	const renderByName = () => {
		switch (name) {
			case 'activity':
				return <path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.5.5 0 0 1-.96 0L9.68 3.18a.5.5 0 0 0-.96 0l-2.35 8.36A2 2 0 0 1 4.44 13H2" />;
			case 'coins':
				return (
					<>
						<circle cx="8" cy="8" r="6" />
						<path d="M18.09 10.37A6 6 0 1 1 10.34 18" />
						<path d="M7 6h1v4" />
						<path d="m16.71 13.88.7.71-2.82 2.82" />
					</>
				);
			case 'package':
				return (
					<>
						<path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z" />
						<path d="M12 22V12" />
						<path d="m3.3 7 7.703 4.734a2 2 0 0 0 1.994 0L20.7 7" />
						<path d="m7.5 4.27 9 5.15" />
					</>
				);
			case 'repeat':
				return (
					<>
						<path d="m17 2 4 4-4 4" />
						<path d="M3 11v-1a4 4 0 0 1 4-4h14" />
						<path d="m7 22-4-4 4-4" />
						<path d="M21 13v1a4 4 0 0 1-4 4H3" />
					</>
				);
			default:
				return null;
		}
	};

	return (
		<span className="npwc-section-icon">
			<svg viewBox="0 0 24 24" aria-hidden="true">
				{renderByName()}
			</svg>
		</span>
	);
}

function SectionTitle({ icon, title, subtitle }) {
	return (
		<div className="npwc-section-title">
			<SectionIcon name={icon} />
			<div>
				<div className="npwc-panel-title">{title}</div>
				{subtitle ? <p>{subtitle}</p> : null}
			</div>
		</div>
	);
}

/** Demo rates for the Pro-locked converter preview (free build has no live rates). */
const DEMO_BTC_RATES = {
	USD: 68420.55,
	EUR: 63250,
};

/**
 * Static, non-interactive preview of the Pro currency converter (rendered behind ProLock).
 */
function CurrencyConverterPreview({ storeCurrency }) {
	const fiat = (storeCurrency || 'USD').toUpperCase();
	const rate = DEMO_BTC_RATES[fiat];
	const receive = rate ? formatAmount(rate, fiat) : '—';

	return (
		<div className="npwc-panel npwc-converter">
			<div className="npwc-converter-head">
				<SectionTitle icon="repeat" title="Currency Converter" subtitle="Live crypto — fiat conversion" />
				<span className="npwc-converter-badge">Rates updated</span>
			</div>
			<div className="npwc-converter-grid">
				<div className="npwc-converter-box">
					<span className="npwc-converter-label">You send</span>
					<div className="npwc-converter-row">
						<span className="npwc-converter-value">1</span>
						<span className="npwc-converter-currency">BTC</span>
					</div>
				</div>
				<span className="npwc-converter-swap" aria-hidden="true">
					⇆
				</span>
				<div className="npwc-converter-box">
					<span className="npwc-converter-label">You receive</span>
					<div className="npwc-converter-row">
						<span className="npwc-converter-value">{receive}</span>
						<span className="npwc-converter-currency">{fiat}</span>
					</div>
				</div>
			</div>
			<p className="npwc-converter-rate">{rate ? `1 BTC = ${receive} ${fiat}` : '1 BTC — live conversion in Pro'}</p>
		</div>
	);
}

function DonutChart({ values }) {
	const total = Object.values(values).reduce((acc, item) => acc + item, 0);
	const radius = 64;
	const circumference = 2 * Math.PI * radius;
	let offset = 0;

	return (
		<div className="npwc-donut-wrap">
			<svg viewBox="0 0 180 180" className="npwc-donut">
				<circle cx="90" cy="90" r={radius} fill="none" stroke="var(--npwc-border)" strokeWidth="16" />
				{Object.entries(values).map(([key, value]) => {
					const percent = total ? value / total : 0;
					const dash = percent * circumference;
					const strokeDasharray = `${dash} ${circumference - dash}`;
					const style = { strokeDasharray, strokeDashoffset: -offset };
					offset += dash;
					return (
						<circle
							key={key}
							cx="90"
							cy="90"
							r={radius}
							fill="none"
							stroke={STATUS_COLORS[key]}
							strokeWidth="16"
							transform="rotate(-90 90 90)"
							style={style}
						/>
					);
				})}
				<text x="90" y="85" textAnchor="middle" className="npwc-donut-number">
					{total ? `${((values.finished / total) * 100).toFixed(1)}%` : '0%'}
				</text>
				<text x="90" y="104" textAnchor="middle" className="npwc-donut-label">
					Finished
				</text>
			</svg>
		</div>
	);
}

function TopCoins({ coins, storeCurrency }) {
	const data = coins && coins.length ? coins : [];
	const max = Math.max(1, ...data.map((coin) => Number(coin.count || 0)));
	const step = max <= 200 ? 50 : max <= 400 ? 100 : 200;
	const yTop = Math.ceil(max / step) * step;
	const yTicks = [0, Math.round(yTop * 0.25), Math.round(yTop * 0.5), Math.round(yTop * 0.75), yTop];

	return (
		<div className="npwc-panel npwc-top-coins-panel">
			<SectionTitle icon="coins" title="Top Coins & Methods" subtitle="By transaction count · 7D" />
			<div className="npwc-bars-wrap">
				<div className="npwc-bars-y">
					{[...yTicks].reverse().map((tick) => (
						<span key={`y-${tick}`}>{tick}</span>
					))}
				</div>
				<div className="npwc-bars-main">
					<div className="npwc-bars-plot">
						{data.map((coin, idx) => {
							const ratio = yTop ? Number(coin.count || 0) / yTop : 0;
							return (
								<div key={coin.coin + idx} className="npwc-bar-slot">
									<div
										className="npwc-bar"
										style={{
											height: `${Math.min(100, Math.max(0, ratio * 100))}%`,
											background: coinColorForSymbol(coin.coin),
										}}
									/>
								</div>
							);
						})}
					</div>
					<div className="npwc-bars-xlabels">
						{data.map((coin) => (
							<span key={coin.coin + '-x'} className="npwc-bar-xlabel">
								{coin.coin}
							</span>
						))}
					</div>
				</div>
			</div>
			<div className="npwc-coin-list">
				{data.map((coin) => (
					<div key={coin.coin + '_row'} className="npwc-coin-row">
						<div className="npwc-coin-left">
							<span className="npwc-coin-badge" style={{ background: coinColorForSymbol(coin.coin) }} aria-hidden="true">
								{coin.coin.slice(0, 2)}
							</span>
							<div className="npwc-coin-text">
								<strong className="npwc-coin-title">{COIN_DISPLAY[coin.coin] || coin.coin}</strong>
								<small>
									{coin.count || 0} tx · AOV {formatAmount((coin.total || 0) / Math.max(1, coin.count || 1), storeCurrency)}
								</small>
							</div>
						</div>
						<strong>{formatAmount(coin.total, storeCurrency)}</strong>
					</div>
				))}
			</div>
		</div>
	);
}

function ViewEyeIcon() {
	return (
		<svg className="npwc-view-eye" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
			<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
			<circle cx="12" cy="12" r="3" />
		</svg>
	);
}

function TransactionsTable({ rows, upgradeUrl }) {
	const cta = upgradeUrl || '#';

	return (
		<div className="npwc-panel npwc-transactions-panel">
			<div className="npwc-transactions-head">
				<SectionTitle icon="package" title="Recent Transactions" subtitle="Live feed from NOWPayments IPN" />
				<div className="npwc-transactions-toolbar">
					<select className="npwc-tx-filter" disabled aria-label="Filter by status">
						<option value="all">All statuses</option>
					</select>
					<a href={cta} target="_blank" rel="noopener noreferrer" className="npwc-tx-view-all">
						View all
					</a>
				</div>
			</div>
			<div className="npwc-table-wrap">
				<table className="npwc-table npwc-table-transactions">
					<thead>
						<tr>
							<th>Order</th>
							<th>Customer</th>
							<th>Amount</th>
							<th>Coin</th>
							<th>Status</th>
							<th>Sub</th>
							<th>Time</th>
							<th className="npwc-th-view" scope="col">
								View
							</th>
						</tr>
					</thead>
					<tbody>
						{rows.map((row) => (
							<tr key={row.orderId}>
								<td className="npwc-td-order">#{row.orderId}</td>
								<td className="npwc-td-muted">{row.customerEmail}</td>
								<td className="npwc-td-amount">{row.amount}</td>
								<td>
									<span className="npwc-coin-pill">
										<span className="npwc-coin-dot" style={{ background: coinColorForSymbol(row.coin) }} />
										{row.coin}
									</span>
								</td>
								<td>
									<span className={`npwc-status npwc-status-${row.statusClass}`}>{row.status}</span>
								</td>
								<td className="npwc-td-muted">{row.subscription}</td>
								<td className="npwc-td-muted">{row.timeAgo}</td>
								<td>
									<a href={row.viewUrl} className="npwc-view-link">
										<ViewEyeIcon />
										View
									</a>
								</td>
							</tr>
						))}
					</tbody>
				</table>
			</div>
		</div>
	);
}

function legendLabel(key) {
	return key.charAt(0).toUpperCase() + key.slice(1);
}

function persistDashboardTheme(theme) {
	const cfg = window.npwcDashboardData || {};
	const url = cfg.themeRestUrl;
	if (!url) {
		return;
	}
	window
		.fetch(url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.restNonce || '',
			},
			body: JSON.stringify({ theme }),
		})
		.catch(() => {});
}

function App() {
	const data = window.npwcDashboardData || {};
	const metrics = data.metrics || {};
	const [theme, setTheme] = useState(data.defaultTheme || 'light');
	const upgradeUrl = data.upgradeUrl || '#';
	const storeCurrency = data.storeCurrency || 'USD';
	const demoCryptoRevenue = Number(data.demoCryptoRevenueTotal) || 0;
	const statusBreakdownValues = data.statusBreakdown || { finished: 0, failed: 0, waiting: 0, refunded: 0 };
	const donutPreviewTotal = Object.values(statusBreakdownValues).reduce((sum, v) => sum + Number(v || 0), 0);
	const statusDonutSubtitle = `Distribution of ${donutPreviewTotal || metrics.totalOrders || 0} orders`;
	const labels = useMemo(() => (data.revenueSeries || []).map((item) => item.label), [data.revenueSeries]);
	const points = useMemo(() => (data.revenueSeries || []).map((item) => Number(item.revenue || 0)), [data.revenueSeries]);

	useEffect(() => {
		const body = document.body;
		const lightClass = 'npwc-dashboard-theme-light';
		if (theme === 'light') {
			body.classList.add(lightClass);
		} else {
			body.classList.remove(lightClass);
		}
		return () => {
			body.classList.remove(lightClass);
		};
	}, [theme]);

	return (
		<div className={`npwc-dashboard npwc-theme-${theme}`}>
			<header className="npwc-header">
				<div className="npwc-brand">
					<img src={data.iconUrl} alt="NOWPayment" />
					<div>
						<strong>NOWPayment</strong>
						<p>Admin console</p>
					</div>
				</div>
				<div className="npwc-theme-toggle-wrap">
					<span>{theme === 'dark' ? 'Dark mode' : 'Light mode'}</span>
					<button
						type="button"
						className={`npwc-theme-toggle ${theme === 'light' ? 'is-light' : ''}`}
						onClick={() => {
							const next = theme === 'dark' ? 'light' : 'dark';
							setTheme(next);
							persistDashboardTheme(next);
						}}
						aria-label="Toggle theme"
					>
						<span className="npwc-theme-knob" />
					</button>
				</div>
			</header>

			<ApiConfigBanner apiConfigured={data.apiConfigured} settingsUrl={data.settingsUrl} docsUrl={data.docsUrl} />

			<section className="npwc-hero">
				<h1>NOWPayments . Dashboard</h1>
				<p>Crypto checkout overview · last 7 days</p>
			</section>

			<section className="npwc-cards">
				<DashboardCard label="Total Crypto Orders" value={metrics.totalOrders || 0} sub="NOWPayment orders" />
				<DashboardCard label="Successful Payments" value={metrics.successfulPayments || 0} sub="Completed + Processing" />
				<DashboardCard label="Failed Payments" value={metrics.failedPayments || 0} sub="Failed orders" />
				<DashboardCard label="Refunded" value={metrics.refundedPayments || 0} sub="Refunded orders" />
				<div className="npwc-card-wrap">
					<ProLock
						upgradeUrl={upgradeUrl}
						className="npwc-pro-lock--card"
						theme={theme}
						modalDescription="See real crypto revenue in store currency, trends, and exports with Pro."
					>
						<DashboardCard label="Crypto Revenue" value={formatAmount(demoCryptoRevenue, storeCurrency)} sub="Store currency" />
					</ProLock>
				</div>
			</section>

			<section className="npwc-grid">
				<ProLock
					upgradeUrl={upgradeUrl}
					className="npwc-pro-lock--panel npwc-pro-lock--converter"
					theme={theme}
					modalDescription="Unlock live crypto ↔ fiat estimates, accurate rates, and full converter tools in Pro."
				>
					<CurrencyConverterPreview storeCurrency={storeCurrency} />
				</ProLock>
			</section>

			<section className="npwc-grid">
				<div className="npwc-panel npwc-panel-lg">
					<SectionTitle icon="activity" title="Payments & Revenue Over Time" subtitle="Last 8 days · store currency" />
					<LineChart points={points} labels={labels} />
				</div>
				<ProLock upgradeUrl={upgradeUrl} className="npwc-pro-lock--panel" theme={theme} modalDescription="See full status distribution, filters & exports.">
					<div className="npwc-panel">
						<SectionTitle icon="activity" title="Payment Status Breakdown" subtitle={statusDonutSubtitle} />
						<DonutChart values={statusBreakdownValues} />
						<div className="npwc-legend">
							{Object.entries(statusBreakdownValues).map(([key, value]) => (
								<div key={key} className="npwc-legend-item">
									<span style={{ background: STATUS_COLORS[key] }} />
									<strong>{legendLabel(key)}</strong>
									<em>{value}</em>
								</div>
							))}
						</div>
					</div>
				</ProLock>
			</section>

			<section className="npwc-grid npwc-grid--coins-tx">
				<ProLock upgradeUrl={upgradeUrl} className="npwc-pro-lock--panel" theme={theme} modalDescription="Unlock top coins & methods analytics, charts, and full breakdown in Pro.">
					<TopCoins coins={data.topCoins || []} storeCurrency={storeCurrency} />
				</ProLock>
				<ProLock
					upgradeUrl={upgradeUrl}
					className="npwc-pro-lock--panel npwc-pro-lock--transactions"
					theme={theme}
					modalDescription="Live IPN feed, search, filters & CSV export."
				>
					<TransactionsTable rows={data.recentTransactions || []} upgradeUrl={upgradeUrl} />
				</ProLock>
			</section>
		</div>
	);
}

const root = document.getElementById('npwc-dashboard-root');
if (root) {
	render(<App />, root);
}
