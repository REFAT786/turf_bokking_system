"""Generate original, license-free SVG football-turf illustrations for TurfBook."""
import random, sys, os

OUT = sys.argv[1]
os.makedirs(OUT, exist_ok=True)


def skyline(rnd, w, base, color, hmin, hmax, step=(40, 110)):
    x, parts = -10, []
    while x < w + 10:
        bw = rnd.randint(*step)
        bh = rnd.randint(hmin, hmax)
        parts.append(f'<rect x="{x}" y="{base - bh}" width="{bw}" height="{bh + 2}" fill="{color}"/>')
        # a few lit windows
        for _ in range(rnd.randint(0, 5)):
            wx = x + rnd.randint(6, max(7, bw - 12)); wy = base - bh + rnd.randint(8, max(9, bh - 14))
            parts.append(f'<rect x="{wx}" y="{wy}" width="5" height="7" fill="#fde68a" opacity="{rnd.choice([0, .35, .6])}"/>')
        x += bw + rnd.randint(-6, 6)
    return ''.join(parts)


def pitch(W, H, horizon, g1, g2, line='#ffffff', line_op=.85, stripes=10):
    """Perspective pitch: far edge at horizon, near edge below the frame."""
    fx1, fx2 = W * .26, W * .74          # far touchline x
    nx1, nx2 = -W * .55, W * 1.55        # near touchline x (off-frame)
    ny = H + H * .55
    p = [f'<polygon points="0,{horizon} {W},{horizon} {W},{H} 0,{H}" fill="{g2}"/>']
    # stripes (perspective bands)
    for i in range(stripes):
        t0, t1 = (i / stripes) ** 1.6, ((i + 1) / stripes) ** 1.6
        y0, y1 = horizon + (ny - horizon) * t0, horizon + (ny - horizon) * t1
        col = g1 if i % 2 == 0 else g2
        p.append(f'<polygon points="0,{y0:.1f} {W},{y0:.1f} {W},{y1:.1f} 0,{y1:.1f}" fill="{col}"/>')
    L = f'stroke="{line}" stroke-opacity="{line_op}" fill="none" stroke-linejoin="round"'
    p.append(f'<polygon points="{fx1},{horizon + 6} {fx2},{horizon + 6} {nx2},{ny} {nx1},{ny}" {L} stroke-width="5"/>')
    # halfway line + centre circle (ellipse in perspective)
    t = .42 ** 1.0
    hy = horizon + 6 + (ny - horizon) * (t ** 1.6)
    lx = fx1 + (nx1 - fx1) * (t ** 1.6); rx = fx2 + (nx2 - fx2) * (t ** 1.6)
    p.append(f'<line x1="{lx:.0f}" y1="{hy:.0f}" x2="{rx:.0f}" y2="{hy:.0f}" {L} stroke-width="5"/>')
    p.append(f'<ellipse cx="{W / 2}" cy="{hy:.0f}" rx="{W * .16:.0f}" ry="{H * .075:.0f}" {L} stroke-width="5"/>')
    p.append(f'<circle cx="{W / 2}" cy="{hy:.0f}" r="5" fill="{line}" fill-opacity="{line_op}"/>')
    # far penalty box + goal
    bw, bh = W * .17, H * .06
    p.append(f'<polyline points="{W / 2 - bw},{horizon + 6} {W / 2 - bw * 1.08},{horizon + 6 + bh} {W / 2 + bw * 1.08},{horizon + 6 + bh} {W / 2 + bw},{horizon + 6}" {L} stroke-width="4"/>')
    gw, gh = W * .055, H * .055
    p.append(f'<rect x="{W / 2 - gw}" y="{horizon + 6 - gh}" width="{gw * 2}" height="{gh}" fill="none" stroke="#ffffff" stroke-width="4"/>')
    for i in range(1, 8):
        x = W / 2 - gw + gw * 2 * i / 8
        p.append(f'<line x1="{x:.0f}" y1="{horizon + 6 - gh}" x2="{x:.0f}" y2="{horizon + 6}" stroke="#ffffff" stroke-opacity=".35" stroke-width="1.5"/>')
    return ''.join(p)


def ball(cx, cy, r):
    return (f'<g transform="translate({cx} {cy})"><ellipse cx="0" cy="{r * .95}" rx="{r * 1.1}" ry="{r * .25}" fill="#000" opacity=".25"/>'
            f'<circle r="{r}" fill="#fff"/>'
            f'<polygon points="0,{-r * .42} {r * .4},{-r * .13} {r * .25},{r * .34} {-r * .25},{r * .34} {-r * .4},{-r * .13}" fill="#111827"/>'
            f'<path d="M0,{-r * .42} L0,{-r * .95} M{r * .4},{-r * .13} L{r * .9},{-r * .3} M{r * .25},{r * .34} L{r * .55},{r * .78} M{-r * .25},{r * .34} L{-r * .55},{r * .78} M{-r * .4},{-r * .13} L{-r * .9},{-r * .3}" stroke="#111827" stroke-width="{r * .07}"/>'
            f'<circle r="{r}" fill="none" stroke="#e5e7eb" stroke-width="{r * .06}"/></g>')


def floodlight(x, top, base, beam_to, color='#fef9c3', op=.22, side=1):
    pole = f'<rect x="{x - 4}" y="{top}" width="8" height="{base - top}" fill="#0f172a" opacity=".85"/>'
    head = ''.join(f'<circle cx="{x - 18 + i * 12}" cy="{top - 4 + (j * 10)}" r="5" fill="{color}"/>' for i in range(4) for j in range(2))
    head = f'<rect x="{x - 26}" y="{top - 12}" width="52" height="24" rx="4" fill="#1e293b"/>' + head
    bx1, bx2, by = beam_to
    beam = f'<polygon points="{x - 20},{top} {x + 20},{top} {bx2},{by} {bx1},{by}" fill="url(#beam)" opacity="{op}"/>'
    return beam + pole + head


def svg(W, H, defs, body, title):
    return (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W} {H}" width="{W}" height="{H}" role="img" aria-label="{title}">'
            f'<title>{title}</title><defs>{defs}</defs>{body}</svg>')


BEAM = '<linearGradient id="beam" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fffbeb" stop-opacity="1"/><stop offset="1" stop-color="#fffbeb" stop-opacity="0"/></linearGradient>'
VIGNETTE = '<radialGradient id="vig" cx=".5" cy=".45" r=".75"><stop offset=".6" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".35"/></radialGradient>'


def scene(name, W, H, seed, sky, pitch_cols, extras, title, horizon_ratio=.5, lights=False, stars=False, sun=None, city=None, net_roof=False, ball_at=None):
    rnd = random.Random(seed)
    hz = H * horizon_ratio
    defs = BEAM + VIGNETTE + f'<linearGradient id="sky" x1="0" y1="0" x2="0" y2="1">' + ''.join(
        f'<stop offset="{o}" stop-color="{c}"/>' for o, c in sky) + '</linearGradient>'
    b = [f'<rect width="{W}" height="{H}" fill="url(#sky)"/>']
    if stars:
        for _ in range(90):
            b.append(f'<circle cx="{rnd.uniform(0, W):.0f}" cy="{rnd.uniform(0, hz * .8):.0f}" r="{rnd.choice([.8, 1.2, 1.8])}" fill="#fff" opacity="{rnd.uniform(.3, .9):.2f}"/>')
    if sun:
        cx, cy, r, c = sun
        b.append(f'<circle cx="{cx}" cy="{cy}" r="{r * 2.2}" fill="{c}" opacity=".18"/><circle cx="{cx}" cy="{cy}" r="{r}" fill="{c}"/>')
    if city:
        far, near = city
        b.append(skyline(rnd, W, hz + 4, far, 40, 150, (50, 120)))
        b.append(skyline(rnd, W, hz + 6, near, 20, 90, (40, 90)))
    if net_roof:
        b.append(f'<rect x="0" y="0" width="{W}" height="{H * .12}" fill="#0f172a" opacity=".55"/>')
        for i in range(0, W + 1, 80):
            b.append(f'<line x1="{i}" y1="0" x2="{W / 2 + (i - W / 2) * .55:.0f}" y2="{hz:.0f}" stroke="#e2e8f0" stroke-opacity=".12" stroke-width="2"/>')
        for i in range(1, 6):
            y = hz * i / 6
            b.append(f'<line x1="0" y1="{y:.0f}" x2="{W}" y2="{y:.0f}" stroke="#e2e8f0" stroke-opacity=".08" stroke-width="2"/>')
        for i in range(5):
            x = W * (i + .5) / 5
            b.append(f'<rect x="{x - 40:.0f}" y="{H * .12 - 6:.0f}" width="80" height="10" rx="5" fill="#f8fafc" opacity=".9"/>')
    # fence behind pitch
    b.append(f'<rect x="0" y="{hz - H * .05:.0f}" width="{W}" height="{H * .05 + 8:.0f}" fill="#000" opacity=".18"/>')
    for i in range(0, W + 1, 14):
        b.append(f'<line x1="{i}" y1="{hz - H * .05:.0f}" x2="{i + 14}" y2="{hz + 6:.0f}" stroke="#fff" stroke-opacity=".10"/>')
    b.append(pitch(W, H, hz, *pitch_cols))
    if lights:
        b.append(floodlight(W * .08, H * .1, hz + 6, (W * .2, W * .7, H), op=.28))
        b.append(floodlight(W * .92, H * .1, hz + 6, (W * .3, W * .8, H), op=.28))
    if ball_at:
        b.append(ball(*ball_at))
    b.append(extras)
    b.append(f'<rect width="{W}" height="{H}" fill="url(#vig)"/>')
    open(os.path.join(OUT, name), 'w').write(svg(W, H, defs, ''.join(b), title))


W, H = 1200, 675
G = {  # pitch colour pairs
    'day': ('#3fa34d', '#36923f'), 'night': ('#2f8a46', '#277a3d'), 'sunset': ('#3d8f45', '#347d3c'),
    'futsal': ('#2563eb', '#1d4ed8'), 'teal': ('#15937a', '#11806a'), 'dawn': ('#3b9150', '#337f46'),
}
scene('turf-1.svg', W, H, 1, [(0, '#7dd3fc'), (1, '#e0f2fe')], G['day'], '', 'Day football turf',
      city=('#94a3b8', '#64748b'), sun=(980, 120, 46, '#fde047'), ball_at=(760, 560, 30))
scene('turf-2.svg', W, H, 2, [(0, '#020617'), (.7, '#1e1b4b'), (1, '#312e81')], G['night'], '', 'Floodlit night turf',
      stars=True, lights=True, city=('#1e293b', '#0f172a'), ball_at=(430, 590, 32))
scene('turf-3.svg', W, H, 3, [(0, '#7c3aed'), (.45, '#f472b6'), (.8, '#fb923c'), (1, '#fde68a')], G['sunset'], '', 'Rooftop turf at sunset',
      sun=(600, 330, 70, '#fff7ed'), city=('#7c2d12', '#431407'), horizon_ratio=.53, ball_at=(820, 580, 28))
scene('turf-4.svg', W, H, 4, [(0, '#0f172a'), (1, '#334155')], G['futsal'], '', 'Indoor futsal court',
      net_roof=True, horizon_ratio=.46, ball_at=(600, 540, 30))
scene('turf-5.svg', W, H, 5, [(0, '#c4b5fd'), (.6, '#fbcfe8'), (1, '#fef3c7')], G['dawn'], '', 'Turf at dawn',
      sun=(220, 300, 40, '#fff'), city=('#a78bfa', '#8b5cf6'), ball_at=(900, 600, 32))
scene('turf-6.svg', W, H, 6, [(0, '#0c4a6e'), (1, '#0e7490')], G['teal'], '', 'Covered turf with lights',
      lights=True, net_roof=True, horizon_ratio=.48, ball_at=(350, 560, 28))
scene('turf-7.svg', W, H, 7, [(0, '#f59e0b'), (.5, '#fcd34d'), (1, '#fef3c7')], G['day'], '', 'Golden hour turf',
      sun=(900, 260, 60, '#fffbeb'), city=('#b45309', '#92400e'), ball_at=(520, 590, 30))
scene('turf-8.svg', W, H, 8, [(0, '#000000'), (.6, '#082f49'), (1, '#0c4a6e')], G['night'], '', 'Late night match',
      stars=True, lights=True, ball_at=(700, 575, 30))

# Wide hero + auth panel + dashboard banners
scene('hero.svg', 1600, 900, 11, [(0, '#020617'), (.6, '#052e16'), (1, '#14532d')], ('#2f8a46', '#277a3d'), '', 'Floodlit football turf at night',
      stars=True, lights=True, city=('#0f172a', '#020617'), horizon_ratio=.52, ball_at=(1180, 790, 40))
scene('auth.svg', 900, 1200, 12, [(0, '#022c22'), (.55, '#065f46'), (1, '#10b981')], ('#2f8a46', '#277a3d'), '', 'Football turf',
      stars=True, lights=True, horizon_ratio=.55, ball_at=(600, 1050, 42))


def banner(name, c1, c2, accent, title):
    Wb, Hb = 1600, 360
    d = (f'<linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{c1}"/><stop offset="1" stop-color="{c2}"/></linearGradient>')
    L = 'stroke="#fff" stroke-opacity=".14" stroke-width="4" fill="none"'
    b = [f'<rect width="{Wb}" height="{Hb}" fill="url(#g)"/>']
    for i in range(0, Wb, 120):
        b.append(f'<rect x="{i}" y="0" width="60" height="{Hb}" fill="#fff" opacity=".035"/>')
    # top-down pitch markings on the right
    x0 = 900
    b.append(f'<rect x="{x0}" y="30" width="660" height="300" rx="6" {L}/>')
    b.append(f'<line x1="{x0 + 330}" y1="30" x2="{x0 + 330}" y2="330" {L}/>')
    b.append(f'<circle cx="{x0 + 330}" cy="180" r="62" {L}/>')
    b.append(f'<rect x="{x0}" y="100" width="90" height="160" {L}/><rect x="{x0 + 570}" y="100" width="90" height="160" {L}/>')
    b.append(f'<circle cx="{x0 + 330}" cy="180" r="6" fill="#fff" fill-opacity=".3"/>')
    b.append(f'<circle cx="1480" cy="40" r="160" fill="{accent}" opacity=".18"/><circle cx="1180" cy="360" r="120" fill="{accent}" opacity=".12"/>')
    b.append(ball(1540, 300, 30))
    open(os.path.join(OUT, name), 'w').write(svg(Wb, Hb, d, ''.join(b), title))


banner('banner-owner.svg', '#064e3b', '#059669', '#a7f3d0', 'Dashboard banner')
banner('banner-admin.svg', '#0f172a', '#1e3a8a', '#93c5fd', 'Admin banner')
banner('banner-player.svg', '#4c1d95', '#be185d', '#fbcfe8', 'Player banner')
print('ok', sorted(os.listdir(OUT)))
