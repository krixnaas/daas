
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>DAAS — The dad app that actually gets it.</title>
  <meta name="description" content="From the first ultrasound to the late-night feeds — DAAS helps you track every moment of becoming, and being, a dad. With heart." />

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
  <!-- Fonts: Playfair Display (serif display) + Inter (body) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;0,900;1,500;1,700;1,900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
  /* =========================================================
   DAAS — Warm Peach / Coral palette
   Fonts: Playfair Display (display), Inter (body)
   ========================================================= */

:root {
  --cream: #fdf7f2;
  --cream-2: #fcefe4;
  --peach: #fbe4d3;
  --peach-soft: #f8d8c2;
  --coral: #e8795a;
  --coral-deep: #d9613f;
  --coral-dark: #b8655a;
  --ink: #2a1e1a;
  --ink-soft: #4a3a34;
  --muted: #8a7870;
  --sage: #b7c9a8;
  --sage-bg: #e4ecd9;
  --lilac: #c9b8d1;
  --lilac-bg: #ede4f0;
  --pink: #f2a8a0;
  --pink-bg: #fce1dd;
  --border-soft: rgba(42, 30, 26, 0.08);
  --shadow-soft: 0 10px 30px rgba(212, 122, 90, 0.08);
  --shadow-lifted: 0 20px 50px rgba(212, 122, 90, 0.12);
}

* { -webkit-font-smoothing: antialiased; }

html { scroll-behavior: smooth; }

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  color: var(--ink);
  background: var(--cream);
  line-height: 1.6;
  padding-top: 80px;
}

/* ---------- Typography ---------- */
.display-hero,
.section-title,
.path-title,
.cta-title {
  font-family: 'Playfair Display', Georgia, serif;
  font-weight: 900;
  letter-spacing: -0.02em;
  line-height: 1.05;
  color: var(--ink);
}

.display-hero {
  font-size: clamp(2.4rem, 5.5vw, 4.3rem);
}

.section-title {
  font-size: clamp(2rem, 4vw, 3.2rem);
  line-height: 1.1;
}

.path-title {
  font-size: clamp(1.7rem, 3vw, 2.4rem);
  margin-top: 0.5rem;
}

.cta-title {
  font-size: clamp(2rem, 5vw, 3.6rem);
  color: #fff;
}

.accent-coral {
  color: var(--coral);
  font-style: italic;
  font-weight: 900;
}

.eyebrow {
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.2em;
  color: var(--coral-deep);
  text-transform: uppercase;
  margin-bottom: 0.75rem;
}

.section-sub {
  color: var(--ink-soft);
  max-width: 620px;
  font-size: 1.05rem;
}

.lead-muted {
  color: var(--ink-soft);
  font-size: 1.1rem;
  max-width: 520px;
}

.small-muted { color: var(--muted); font-size: 0.9rem; }

/* ---------- Navbar ---------- */
.daas-nav {
  background: rgba(253, 247, 242, 0.82);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border-bottom: 1px solid var(--border-soft);
  padding: 0.85rem 0;
  transition: background 0.3s ease;
}

.brand-logo {
  width: 38px; height: 38px;
  background: linear-gradient(135deg, var(--coral), var(--coral-deep));
  color: #fff;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  box-shadow: 0 4px 12px rgba(232, 121, 90, 0.25);
}
.brand-logo.small-logo { width: 30px; height: 30px; font-size: 0.9rem; border-radius: 9px; }

.brand-text {
  font-family: 'Playfair Display', serif;
  font-weight: 900;
  font-size: 1.35rem;
  letter-spacing: -0.02em;
  color: var(--ink);
}

.navbar .nav-link {
  color: var(--ink-soft);
  font-weight: 500;
  font-size: 0.95rem;
  padding: 0.5rem 0.9rem !important;
  transition: color 0.2s;
}
.navbar .nav-link:hover { color: var(--coral); }

/* ---------- Buttons ---------- */
.btn-coral {
  background: var(--coral);
  color: #fff;
  border: none;
  font-weight: 600;
  padding: 0.7rem 1.6rem;
  box-shadow: 0 6px 18px rgba(232, 121, 90, 0.35);
  transition: all 0.25s ease;
}
.btn-coral:hover {
  background: var(--coral-deep);
  color: #fff;
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(232, 121, 90, 0.45);
}

.btn-outline-soft {
  background: rgba(255, 255, 255, 0.7);
  color: var(--ink);
  border: 1px solid var(--border-soft);
  font-weight: 600;
  transition: all 0.25s;
}
.btn-outline-soft:hover {
  background: #fff;
  color: var(--coral-deep);
  border-color: var(--coral);
}

.btn-white {
  background: #fff;
  color: var(--coral-deep);
  border: none;
  font-weight: 700;
  transition: all 0.25s;
}
.btn-white:hover {
  background: #fff2ea;
  color: var(--coral-deep);
  transform: translateY(-2px);
}

/* ---------- Badge ---------- */
.badge-soft {
  background: rgba(232, 121, 90, 0.1);
  color: var(--coral-deep);
  border: 1px solid rgba(232, 121, 90, 0.2);
  border-radius: 999px;
  padding: 0.45rem 1rem;
  font-size: 0.82rem;
  font-weight: 600;
}

/* ---------- Hero ---------- */
.hero-section {
  position: relative;
  padding: 5rem 0 6rem;
  background: linear-gradient(180deg, var(--cream) 0%, var(--cream-2) 60%, var(--cream) 100%);
  overflow: hidden;
}

.hero-section::before {
  content: "";
  position: absolute;
  top: -100px; right: -100px;
  width: 400px; height: 400px;
  background: radial-gradient(circle, rgba(232, 121, 90, 0.12), transparent 70%);
  border-radius: 50%;
  z-index: 0;
}

.hero-section > .container { position: relative; z-index: 1; }

.hero-visual {
  position: relative;
  border-radius: 28px;
  overflow: visible;
}
.hero-img {
  width: 100%;
  height: 520px;
  object-fit: cover;
  border-radius: 28px;
  box-shadow: var(--shadow-lifted);
}

.floating-card {
  position: absolute;
  background: #fff;
  padding: 0.9rem 1.1rem;
  border-radius: 18px;
  box-shadow: 0 12px 35px rgba(42, 30, 26, 0.12);
  display: flex;
  gap: 0.75rem;
  align-items: center;
  animation: floaty 5s ease-in-out infinite;
}
.floating-card-top { top: 30px; left: -20px; }
.floating-card-bottom { bottom: 30px; right: -15px; animation-delay: 2s; }

.dot-check {
  width: 34px; height: 34px;
  border-radius: 50%;
  background: var(--sage-bg);
  color: #5b7a3d;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}

@keyframes floaty {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}

/* ---------- Avatar stack ---------- */
.avatar-stack { display: flex; }
.avatar {
  width: 36px; height: 36px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-weight: 700;
  font-size: 0.82rem;
  border: 2px solid #fff;
  flex-shrink: 0;
}
.avatar-stack .avatar:not(:first-child) { margin-left: -10px; }

/* ---------- Features ---------- */
.features-section {
  padding: 6rem 0;
  background: var(--cream);
}

.feature-card {
  background: #fff;
  border-radius: 20px;
  padding: 1.6rem 1.4rem;
  border: 1px solid var(--border-soft);
  transition: all 0.3s ease;
  height: 100%;
}
.feature-card:hover {
  transform: translateY(-6px);
  box-shadow: var(--shadow-lifted);
  border-color: rgba(232, 121, 90, 0.2);
}

.feature-icon {
  width: 46px; height: 46px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  margin-bottom: 1rem;
}
.icon-coral { background: var(--pink-bg); color: var(--coral-deep); }
.icon-coral-solid { background: var(--coral); color: #fff; }
.icon-pink { background: var(--pink-bg); color: #c85d4e; }
.icon-sage { background: var(--sage-bg); color: #5b7a3d; }
.icon-cream { background: #fef1d9; color: #b8883a; }
.icon-lilac { background: var(--lilac-bg); color: #7b5d8f; }

.feature-title {
  font-family: 'Playfair Display', serif;
  font-size: 1.15rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: var(--ink);
}
.feature-desc { color: var(--ink-soft); font-size: 0.93rem; margin-bottom: 0; line-height: 1.55; }

/* ---------- Journey ---------- */
.journey-section {
  padding: 6rem 0;
  background: linear-gradient(180deg, var(--cream-2), var(--cream));
}

.mb-6 { margin-bottom: 5rem !important; }

.illustration-card {
  border-radius: 24px;
  overflow: hidden;
  box-shadow: var(--shadow-soft);
  background: var(--peach);
}
.illustration-card img {
  width: 100%;
  height: 380px;
  object-fit: cover;
  display: block;
}
.illustration-peach { background: var(--peach); }

.check-list {
  list-style: none;
  padding: 0;
  margin: 0;
}
.check-list li {
  padding: 0.55rem 0;
  color: var(--ink-soft);
  display: flex;
  gap: 0.7rem;
  align-items: flex-start;
  font-size: 1rem;
}
.check-list li i {
  color: var(--coral);
  font-size: 1.1rem;
  flex-shrink: 0;
  margin-top: 2px;
}
.inline-emoji { font-style: normal; }

/* ---------- Tracking ---------- */
.tracking-section {
  padding: 6rem 0;
  background: var(--cream);
}

.stat-pill {
  background: var(--peach);
  border-radius: 18px;
  padding: 1.1rem 1.3rem;
}
.stat-num {
  font-family: 'Playfair Display', serif;
  font-weight: 900;
  color: var(--coral-deep);
  font-size: 1.8rem;
  line-height: 1;
}
.stat-label { font-size: 0.85rem; color: var(--ink-soft); margin-top: 0.25rem; }

.activity-card {
  background: #fff;
  border-radius: 24px;
  padding: 1.6rem;
  box-shadow: var(--shadow-lifted);
  border: 1px solid var(--border-soft);
}
.activity-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border-soft);
  margin-bottom: 0.75rem;
}
.activity-list { display: flex; flex-direction: column; }
.activity-item {
  display: flex;
  align-items: center;
  gap: 0.9rem;
  padding: 0.9rem 0;
  border-bottom: 1px dashed rgba(42, 30, 26, 0.06);
}
.activity-item:last-child { border-bottom: 0; }
.a-icon {
  width: 42px; height: 42px;
  border-radius: 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* ---------- Community ---------- */
.community-section {
  padding: 6rem 0;
  background: linear-gradient(180deg, var(--cream), var(--cream-2));
}

.dad-card {
  background: #fff;
  border-radius: 20px;
  padding: 1.5rem;
  border: 1px solid var(--border-soft);
  transition: all 0.3s ease;
  height: 100%;
}
.dad-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lifted);
}
.dad-card p { color: var(--ink-soft); }

/* ---------- CTA ---------- */
.cta-section {
  padding: 5rem 0;
  background: var(--cream);
}
.cta-card {
  background: linear-gradient(135deg, var(--coral) 0%, var(--coral-deep) 100%);
  border-radius: 32px;
  padding: 4rem 2rem;
  color: #fff;
  position: relative;
  overflow: hidden;
  box-shadow: 0 30px 60px rgba(232, 121, 90, 0.25);
}
.cta-card::before {
  content: "";
  position: absolute;
  top: -80px; left: -80px;
  width: 260px; height: 260px;
  background: rgba(255,255,255,0.08);
  border-radius: 50%;
}
.cta-card::after {
  content: "";
  position: absolute;
  bottom: -60px; right: -60px;
  width: 200px; height: 200px;
  background: rgba(255,255,255,0.06);
  border-radius: 50%;
}
.cta-card > * { position: relative; z-index: 1; }

.cta-icon {
  width: 56px; height: 56px;
  background: rgba(255,255,255,0.22);
  border-radius: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
}
.cta-sub {
  color: rgba(255,255,255,0.92);
  font-size: 1.1rem;
  max-width: 560px;
  margin: 0 auto 2rem;
}
.waitlist-form { max-width: 560px; margin: 0 auto; }
.waitlist-form .form-control {
  border: none;
  box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}
.waitlist-form .form-control:focus {
  box-shadow: 0 6px 26px rgba(0,0,0,0.15);
  border-color: transparent;
}

#waitlistMsg { color: #fff; font-weight: 500; }
#waitlistMsg.is-success { color: #ffe7d8; }
#waitlistMsg.is-error { color: #ffd2c9; }

/* ---------- Footer ---------- */
.footer-section {
  padding: 2.5rem 0;
  background: var(--cream);
  border-top: 1px solid var(--border-soft);
}
.footer-link {
  color: var(--ink-soft);
  text-decoration: none;
  font-size: 0.9rem;
  transition: color 0.2s;
}
.footer-link:hover { color: var(--coral); }

/* ---------- Responsive ---------- */
@media (max-width: 991.98px) {
  body { padding-top: 72px; }
  .hero-img { height: 420px; }
  .floating-card-top { left: 10px; }
  .floating-card-bottom { right: 10px; }
  .navbar-collapse {
    background: #fff;
    border-radius: 16px;
    padding: 1rem;
    margin-top: 0.75rem;
    box-shadow: var(--shadow-soft);
  }
  .navbar-collapse .btn-coral { margin-top: 0.75rem; display: inline-block; }
}

@media (max-width: 575.98px) {
  .hero-section { padding: 3rem 0 4rem; }
  .features-section,
  .journey-section,
  .tracking-section,
  .community-section { padding: 4rem 0; }
  .hero-img { height: 340px; border-radius: 20px; }
  .floating-card { padding: 0.7rem 0.9rem; }
  .floating-card-top { top: 15px; }
  .floating-card-bottom { bottom: 15px; }
  .cta-card { padding: 3rem 1.25rem; border-radius: 24px; }
  .illustration-card img { height: 280px; }
}</style>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg fixed-top daas-nav" data-testid="main-navbar">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2" href="#" data-testid="nav-logo">
        <span class="brand-logo"><i class="bi bi-balloon-heart-fill"></i></span>
        <span class="brand-text">DAAS.</span>
      </a>
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Toggle navigation" data-testid="nav-toggle">
        <i class="bi bi-list fs-3"></i>
      </button>
      <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav mx-auto gap-lg-4">
          <li class="nav-item"><a class="nav-link" href="#features" data-testid="nav-features">Features</a></li>
          <li class="nav-item"><a class="nav-link" href="#journey" data-testid="nav-journey">Journey</a></li>
          <li class="nav-item"><a class="nav-link" href="#tracking" data-testid="nav-tracking">Tracking</a></li>
          <li class="nav-item"><a class="nav-link" href="#community" data-testid="nav-community">Community</a></li>
        </ul>
        @guest
                <a href="/register" 
                   class="btn btn-coral rounded-pill px-4 py-2 d-flex align-items-center gap-2" 
                   data-testid="nav-cta-btn">
                    Join now 
                    <i class="bi bi-arrow-right"></i>
                </a>
            @else
                <a href="/dashboard" 
                   class="btn btn-coral rounded-pill px-4 py-2" 
                   data-testid="nav-cta-btn">
                    Go to Dashboard
                </a>
            @endguest
      </div>
    </div>
  </nav>

  <!-- Hero -->
  <section class="hero-section" data-testid="hero-section">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-6">
          <span class="badge-soft mb-4" data-testid="hero-badge">
            <i class="bi bi-stars"></i> DAAS —  Dad as a Service
          </span>
          <h1 class="display-hero mb-4">
            The dad app that<br />
            <em class="accent-coral">actually</em> gets it.
          </h1>
          <p class="lead-muted mb-4">
            From the first ultrasound to the late-night feeds — DAAS helps you
            track every moment of becoming, and being, a dad. With heart.
          </p>
          <div class="d-flex flex-wrap gap-3 mb-4">
            <a href="#cta" class="btn btn-coral btn-lg rounded-pill px-4" data-testid="hero-start-btn">
              Start your journey <i class="bi bi-arrow-right ms-1"></i>
            </a>
            <a href="#features" class="btn btn-outline-soft btn-lg rounded-pill px-4" data-testid="hero-features-btn">
              <i class="bi bi-play-circle me-1"></i> See features
            </a>
          </div>
          <div class="d-flex align-items-center gap-3 pt-2">
            <div class="avatar-stack">
              <span class="avatar" style="background:#f4a68b">M</span>
              <span class="avatar" style="background:#e88a6e">J</span>
              <span class="avatar" style="background:#d97757">R</span>
              <span class="avatar" style="background:#b8655a">K</span>
            </div>
            <p class="mb-0 small-muted"><strong>9,400+</strong> dads in the waitlist</p>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="hero-visual" data-testid="hero-visual">
            <img src="https://images.unsplash.com/photo-1543342384-6ee49c4e27a6?auto=format&fit=crop&w=900&q=80" alt="Dad holding baby" class="hero-img" onerror="this.src='https://images.pexels.com/photos/3875080/pexels-photo-3875080.jpeg?auto=compress&cs=tinysrgb&w=900'" />
            <div class="floating-card floating-card-top" data-testid="hero-card-size">
              <span class="dot-check"><i class="bi bi-check-lg"></i></span>
              <div>
                <div class="small text-muted">Week 18</div>
                <div class="fw-semibold">Size of an avocado</div>
              </div>
            </div>
            <div class="floating-card floating-card-bottom" data-testid="hero-card-feed">
              <div class="small text-muted">Next feed in</div>
              <div class="fw-bold fs-4 accent-coral">2h 14m</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Features -->
  <section id="features" class="features-section" data-testid="features-section">
    <div class="container">
      <div class="text-center mb-5">
        <span class="eyebrow" data-testid="features-eyebrow">FEATURES</span>
        <h2 class="section-title">
          Everything a dad needs,<br />
          <em class="accent-coral">nothing</em> he doesn't.
        </h2>
        <p class="section-sub mx-auto">
          Built around real fatherhood — from pregnancy week 1 to teaching your kid to ride a bike.
        </p>
      </div>
      <div class="row g-4">
        <div class="col-md-6 col-lg-3">
          <div class="feature-card" data-testid="feature-pregnancy">
            <div class="feature-icon icon-coral"><i class="bi bi-flower2"></i></div>
            <h3 class="feature-title">Pregnancy Tracking</h3>
            <p class="feature-desc">Weekly milestones, baby size compared to fruits or animals, expected weight & growth.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="feature-card" data-testid="feature-mom">
            <div class="feature-icon icon-pink"><i class="bi bi-heart-pulse"></i></div>
            <h3 class="feature-title">Mom's Wellness</h3>
            <p class="feature-desc">Log activities, medicines, sleep, and breast pump info. Smart reminders for next dose.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="feature-card" data-testid="feature-baby">
            <div class="feature-icon icon-sage"><i class="bi bi-person-heart"></i></div>
            <h3 class="feature-title">Baby Profile</h3>
            <p class="feature-desc">Track weight, height, head circumference, blood group and umbilical cord milestones.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="feature-card" data-testid="feature-activity">
            <div class="feature-icon icon-cream"><i class="bi bi-activity"></i></div>
            <h3 class="feature-title">Activity Log</h3>
            <p class="feature-desc">Breast feed, formula, dirty nappies, sleep cycles, & vaccines — all in one tap.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="feature-card" data-testid="feature-photo">
            <div class="feature-icon icon-lilac"><i class="bi bi-camera"></i></div>
            <h3 class="feature-title">Photo Timeline</h3>
            <p class="feature-desc">Daily or weekly photos with auto-timestamps. Watch your little one grow.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="feature-card" data-testid="feature-reminders">
            <div class="feature-icon icon-pink"><i class="bi bi-bell"></i></div>
            <h3 class="feature-title">Smart Reminders</h3>
            <p class="feature-desc">Never miss a feed, medicine or vaccine. Custom alerts that learn your routine.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="feature-card" data-testid="feature-dadsays">
            <div class="feature-icon icon-sage"><i class="bi bi-chat-square-dots"></i></div>
            <h3 class="feature-title">DAD Says</h3>
            <p class="feature-desc">Twitter-style thoughts, mistakes & wisdom. Like, share, learn from other dads.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="feature-card" data-testid="feature-ai">
            <div class="feature-icon icon-coral-solid"><i class="bi bi-stars"></i></div>
            <h3 class="feature-title">DAAS AI</h3>
            <p class="feature-desc">Your 24/7 AI co-parent. Ask anything from feeding tips to sleep schedules.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Journey -->
  <section id="journey" class="journey-section" data-testid="journey-section">
    <div class="container">
      <div class="text-center mb-5">
        <span class="eyebrow">YOUR JOURNEY</span>
        <h2 class="section-title">Two paths. <em class="accent-coral">One amazing</em> ride.</h2>
      </div>

      <!-- Path 1 -->
      <div class="row align-items-center g-5 mb-6">
        <div class="col-lg-6 order-lg-1">
          <div class="illustration-card illustration-peach" data-testid="journey-path1-img">
            <img src="https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?auto=format&fit=crop&w=800&q=80" alt="Pregnancy journey" onerror="this.src='https://images.pexels.com/photos/1973270/pexels-photo-1973270.jpeg?auto=compress&cs=tinysrgb&w=800'" />
          </div>
        </div>
        <div class="col-lg-6 order-lg-2">
          <span class="eyebrow">PATH 01 • TO BE DAD</span>
          <h3 class="path-title">Walk the 9-month road, together.</h3>
          <ul class="check-list mt-4">
            <li><i class="bi bi-check-circle-fill"></i> Set due date, gender, baby type & a name (if you've picked one)</li>
            <li><i class="bi bi-check-circle-fill"></i> Track mom's daily activity, mood, meals & medical visits</li>
            <li><i class="bi bi-check-circle-fill"></i> Weekly pregnancy info — size by fruit <span class="inline-emoji">🍎</span> or animal <span class="inline-emoji">🐾</span></li>
            <li><i class="bi bi-check-circle-fill"></i> One-tap photos to remember every bump milestone</li>
            <li><i class="bi bi-check-circle-fill"></i> Labor started button & 'Baby is Here' celebration moment</li>
          </ul>
        </div>
      </div>

      <!-- Path 2 -->
      <div class="row align-items-center g-5">
        <div class="col-lg-6">
          <span class="eyebrow">PATH 02 • EXISTING DAD</span>
          <h3 class="path-title">Already a dad? Pick up where life left off.</h3>
          <ul class="check-list mt-4">
            <li><i class="bi bi-check-circle-fill"></i> Add each kid with name, DOB & profile — as many as you have</li>
            <li><i class="bi bi-check-circle-fill"></i> Switchable tabs per child (Mom, Kid 1, Kid 2…) drag to reorder</li>
            <li><i class="bi bi-check-circle-fill"></i> Resume mom's activity tracking if she's expecting again</li>
            <li><i class="bi bi-check-circle-fill"></i> Vaccine records, growth charts & milestone memories</li>
            <li><i class="bi bi-check-circle-fill"></i> Smart reminders that respect each kid's unique routine</li>
          </ul>
        </div>
        <div class="col-lg-6">
          <div class="illustration-card" data-testid="journey-path2-img">
            <img src="https://images.unsplash.com/photo-1519689680058-324335c77eba?auto=format&fit=crop&w=800&q=80" alt="Baby feet" onerror="this.src='https://images.pexels.com/photos/1648375/pexels-photo-1648375.jpeg?auto=compress&cs=tinysrgb&w=800'" />
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Tracking -->
  <section id="tracking" class="tracking-section" data-testid="tracking-section">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-6">
          <span class="eyebrow">DAILY TRACKING</span>
          <h2 class="section-title mt-2">Tap. Log. <em class="accent-coral">Done.</em></h2>
          <p class="section-sub ps-0 mx-0 mt-3">
            From wet nappies (•, ••, •••, ••••) to stool color and feeding amounts — every detail of baby care logged in seconds, not minutes. Built for one-handed use, because the other one's holding the baby.
          </p>
          <div class="row g-3 mt-3">
            <div class="col-6">
              <div class="stat-pill" data-testid="stat-log-time">
                <div class="stat-num">3 sec</div>
                <div class="stat-label">avg. log time</div>
              </div>
            </div>
            <div class="col-6">
              <div class="stat-pill" data-testid="stat-metrics">
                <div class="stat-num">12+</div>
                <div class="stat-label">trackable metrics</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="activity-card" data-testid="activity-log-card">
            <div class="activity-header">
              <div>
                <div class="small text-muted">Today · Baby Mia</div>
                <div class="fw-bold fs-4">Activity Log</div>
              </div>
              <span class="avatar" style="background:#d97757">M</span>
            </div>
            <div class="activity-list">
              <div class="activity-item">
                <span class="a-icon icon-coral"><i class="bi bi-droplet-fill"></i></span>
                <div class="flex-grow-1">
                  <div class="fw-semibold">Breast Feed</div>
                  <div class="small text-muted">L / R · scale 1–3 fullness</div>
                </div>
                <span class="small text-muted">now</span>
              </div>
              <div class="activity-item">
                <span class="a-icon icon-cream"><i class="bi bi-cup-hot-fill"></i></span>
                <div class="flex-grow-1">
                  <div class="fw-semibold">Formula & CBF</div>
                  <div class="small text-muted">Record in ml, all together</div>
                </div>
                <span class="small text-muted">now</span>
              </div>
              <div class="activity-item">
                <span class="a-icon icon-sage"><i class="bi bi-capsule"></i></span>
                <div class="flex-grow-1">
                  <div class="fw-semibold">Medicine Log</div>
                  <div class="small text-muted">Next dose auto-calculated</div>
                </div>
                <span class="small text-muted">now</span>
              </div>
              <div class="activity-item">
                <span class="a-icon icon-lilac"><i class="bi bi-moon-stars-fill"></i></span>
                <div class="flex-grow-1">
                  <div class="fw-semibold">Sleep Cycles</div>
                  <div class="small text-muted">Naps, nights & patterns</div>
                </div>
                <span class="small text-muted">now</span>
              </div>
              <div class="activity-item">
                <span class="a-icon icon-cream"><i class="bi bi-camera-fill"></i></span>
                <div class="flex-grow-1">
                  <div class="fw-semibold">Photo Timeline</div>
                  <div class="small text-muted">Daily or weekly snaps</div>
                </div>
                <span class="small text-muted">now</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Community -->
  <section id="community" class="community-section" data-testid="community-section">
    <div class="container">
      <div class="text-center mb-5">
        <span class="eyebrow">DAD SAYS</span>
        <h2 class="section-title">A feed of <em class="accent-coral">real dads</em>, real talk.</h2>
        <p class="section-sub mx-auto">Share thoughts, mistakes, wisdom. Learn from dads in the trenches with you.</p>
      </div>
      <div class="row g-4">
        <div class="col-md-4">
          <div class="dad-card" data-testid="dad-card-1">
            <div class="d-flex align-items-center gap-3 mb-3">
              <span class="avatar" style="background:#d97757">M</span>
              <div>
                <div class="fw-bold">Marcus, dad of 2</div>
                <div class="small text-muted">3h ago</div>
              </div>
            </div>
            <p class="mb-3">Pro tip: warm the wipes between your hands before a 3am change. You're welcome.</p>
            <div class="d-flex gap-3 small text-muted">
              <span><i class="bi bi-heart"></i> 142</span>
              <span><i class="bi bi-chat"></i> Reply</span>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="dad-card" data-testid="dad-card-2">
            <div class="d-flex align-items-center gap-3 mb-3">
              <span class="avatar" style="background:#e88a6e">R</span>
              <div>
                <div class="fw-bold">Raj, new dad</div>
                <div class="small text-muted">6h ago</div>
              </div>
            </div>
            <p class="mb-3">Mistake #47: trying to assemble the crib without reading the manual. Took 4 hours. 😅</p>
            <div class="d-flex gap-3 small text-muted">
              <span><i class="bi bi-heart"></i> 89</span>
              <span><i class="bi bi-chat"></i> Reply</span>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="dad-card" data-testid="dad-card-3">
            <div class="d-flex align-items-center gap-3 mb-3">
              <span class="avatar" style="background:#b8655a">K</span>
              <div>
                <div class="fw-bold">Ken, dad of 1</div>
                <div class="small text-muted">1d ago</div>
              </div>
            </div>
            <p class="mb-3">She smiled for the first time today. Real one, not gas. I cried a little.</p>
            <div class="d-flex gap-3 small text-muted">
              <span><i class="bi bi-heart"></i> 312</span>
              <span><i class="bi bi-chat"></i> Reply</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
<section id="cta" class="cta-section" data-testid="cta-section">
    <div class="container">
        <div class="cta-card text-center">
            <span class="cta-icon"><i class="bi bi-balloon-heart-fill"></i></span>
            <h2 class="cta-title">Be the dad you<br />always wanted to be.</h2>
            <p class="cta-sub">Join thousands of dads building better routines, deeper bonds, and lifelong memories.</p>

            <!-- Join Now Button -->
            <a href="/register" 
               class="btn btn-white btn-lg rounded-pill px-5 py-3 mt-3">
                Join Now <i class="bi bi-arrow-right ms-2"></i>
            </a>

            <p class="small text-white-50 mt-4">
                Free to start • Takes less than 30 seconds
            </p>
        </div>
    </div>
</section>

  <!-- Footer -->
  <footer class="footer-section" data-testid="footer-section">
    <div class="container">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
          <span class="brand-logo small-logo"><i class="bi bi-balloon-heart-fill"></i></span>
          <div>
            <div class="fw-bold">DAAS.</div>
            <div class="small text-muted">Not An App — Built with <i class="bi bi-heart-fill text-danger"></i> for dads</div>
          </div>
        </div>
        <ul class="list-inline mb-0">
          <li class="list-inline-item me-4"><a href="#" class="footer-link" data-testid="footer-privacy">Privacy</a></li>
          <li class="list-inline-item me-4"><a href="#" class="footer-link" data-testid="footer-terms">Terms</a></li>
          <li class="list-inline-item"><a href="#" class="footer-link" data-testid="footer-contact">Contact</a></li>
        </ul>
        <div class="small text-muted">© 2026 DAAS</div>
      </div>
    </div>
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="app.js"></script>
<script defer src="https://static.cloudflareinsights.com/beacon.min.js/v8c78df7c7c0f484497ecbca7046644da1771523124516" integrity="sha512-8DS7rgIrAmghBFwoOTujcf6D9rXvH8xm8JQ1Ja01h9QX8EzXldiszufYa4IFfKdLUKTTrnSFXLDkUEOTrZQ8Qg==" data-cf-beacon='{"version":"2024.11.0","token":"7f7b0fd8732c4326aae4b9d58d5c514a","server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
</body>
</html>
