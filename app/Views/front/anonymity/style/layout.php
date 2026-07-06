        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg);
            color: var(--fg);
            font-family: var(--font-body);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            line-height: 1.6;
            overflow-x: hidden;
            transition: background-color 0.3s ease;
        }

        body.progress-active {
            background-color: #000000 !important;
        }

        body.start-screen-active {
            overflow: hidden;
        }

        body.start-screen-active .container {
            justify-content: center;
            align-items: center;
            padding-top: 0;
            padding-bottom: 0;
            min-height: 100vh;
        }

        body.start-screen-active .start-screen {
            margin: 0;
        }

        .container {
            width: min(1180px, calc(100% - 40px));
            margin: 0 auto;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            padding-top: 82px;
            padding-bottom: 56px;
        }

        /* Top Progress Bar */
        #progress-container {
            display: none;
        }

        #progress-bar {
            width: 0%;
            height: 100%;
            background: var(--fg);
            transition: width 0.15s ease-out;
        }

        .brand-mark {
            position: fixed;
            bottom: 22px;
            left: 24px;
            z-index: 20;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--fg);
            text-decoration: none;
            border: none;
            background: transparent;
            padding: 0;
            backdrop-filter: none;
        }

        .brand-mark img {
            width: 30px;
            height: 30px;
            object-fit: contain;
            border-radius: 4px;
        }

        .brand-mark-text {
            display: flex;
            flex-direction: column;
            line-height: 1.05;
        }

        .brand-kicker {
            font-family: var(--font-mono);
            font-size: 0.58rem;
            text-transform: uppercase;
            color: var(--fg-dim);
        }

        .brand-name {
            font-family: var(--font-display);
            font-size: 1.35rem;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: var(--fg);
        }

        .logo {
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 1.15rem;
            letter-spacing: 0;
            text-transform: uppercase;
            color: var(--fg);
        }

        .logo span {
            color: var(--fg-muted);
        }

        .status-badge-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            background-color: var(--surface);
            padding: 6px 12px;
            border-radius: 4px;
            border: 1px solid var(--border);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--info);
        }

        .status-dot.scanning {
            animation: pulse 1s infinite alternate;
        }

        .status-dot.complete {
            background-color: var(--safe);
        }

        /* Start Screen styling */
        .start-screen {
            width: min(860px, 100%);
            max-width: 860px;
            margin: 56px auto 70px;
            text-align: left;
            max-height: 720px;
            overflow: hidden;
            transition: opacity 0.22s ease, transform 0.22s ease, max-height 0.28s ease, margin 0.28s ease;
        }

        .start-screen.is-leaving {
            opacity: 0;
            transform: translateY(-10px) scaleY(0.94);
            max-height: 0;
            margin-top: 0;
            margin-bottom: 0;
            pointer-events: none;
        }

        .start-screen h1 {
            font-family: 'Michroma', var(--font-display);
            font-size: clamp(1.8rem, 5vw, 3.8rem);
            font-weight: 400;
            line-height: 1.1;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            margin-bottom: 22px;
            text-wrap: balance;
        }

        .start-screen p.subtitle {
            font-size: clamp(1.25rem, 2vw, 1.55rem);
            color: var(--fg);
            max-width: 700px;
            margin-bottom: 32px;
            line-height: 1.8;
        }

        .start-screen p.subtitle span {
            background-color: #083ca6;
            padding: 4px 12px;
            box-decoration-break: clone;
            -webkit-box-decoration-break: clone;
        }

        .scan-note {
            max-width: 680px;
            margin: -12px 0 30px;
            color: var(--fg-muted);
            font-family: var(--font-mono);
            font-size: 0.78rem;
            line-height: 1.55;
        }

        .trust-box {
            border: 1px solid #4B5694;
            background-color: var(--surface);
            padding: 24px;
            text-align: left;
            margin-bottom: 28px;
            border-radius: 6px;
        }

        .trust-box-title {
            font-family: var(--font-mono);
            font-weight: 700;
            margin-bottom: 8px;
            font-size: 0.76rem;
            color: var(--fg);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .trust-box p {
            color: var(--fg-muted);
            font-size: 1.05rem;
            line-height: 1.6;
        }

        .btn-scan {
            background-color: var(--fg);
            color: var(--bg);
            border: none;
            padding: 18px 52px;
            font-family: var(--font-display);
            font-weight: 400;
            font-size: 1.85rem;
            cursor: pointer;
            border-radius: 0px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            box-shadow: 6px 6px 0px var(--accent);
        }

        .btn-scan:hover {
            color: var(--accent);
        }

        /* Progress Screen styling */
        .progress-screen-container {
            width: min(720px, 100%);
            margin: 76px auto;
            text-align: center;
            opacity: 0;
            transform: translateY(12px);
            transition: opacity 0.24s ease, transform 0.24s ease;
        }

        .progress-screen-container.is-active {
            opacity: 1;
            transform: translateY(0);
        }

        .progress-text {
            font-family: var(--font-display);
            font-weight: 400;
            font-size: clamp(2rem, 5vw, 3.4rem);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-top: 22px;
            margin-bottom: 8px;
            color: var(--fg);
        }

        .aperture-stage {
            position: relative;
            width: min(520px, 100%);
            aspect-ratio: 16 / 9;
            margin: 0 auto;
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--surface-strong);
        }

        .aperture-stage::before,
        .aperture-stage::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
        }

        .aperture-stage::before {
            background:
                linear-gradient(90deg, rgba(255, 250, 243, 0.08) 1px, transparent 1px),
                linear-gradient(0deg, rgba(255, 250, 243, 0.06) 1px, transparent 1px);
            background-size: 34px 34px;
        }

        .aperture-stage::after {
            border: 1px solid rgba(255, 250, 243, 0.08);
            inset: 18px;
            border-radius: 4px;
        }

        .aperture-shutter {
            position: absolute;
            left: 0;
            width: 100%;
            height: 50%;
            background: var(--surface);
            transition: transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 2;
        }

        .aperture-shutter.top {
            top: 0;
            transform-origin: top;
            border-bottom: 1px solid rgba(255, 250, 243, 0.22);
        }

        .aperture-shutter.bottom {
            bottom: 0;
            transform-origin: bottom;
            border-top: 1px solid rgba(255, 250, 243, 0.22);
            background: var(--surface-strong);
        }

        .aperture-line {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 84%;
            height: 80px;
            transform: translate(-50%, -50%) scaleX(0.08);
            transform-origin: center;
            transition: transform 0.48s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.22s ease;
            z-index: 3;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: visible;
        }

        .progress-screen-container.is-opening .aperture-line {
            transform: translate(-50%, -50%) scaleX(1);
        }

        .progress-screen-container.is-active .aperture-shutter.top {
            transform: translateY(-100%);
        }

        .progress-screen-container.is-active .aperture-shutter.bottom {
            transform: translateY(100%);
        }

        .progress-screen-container.is-active .aperture-line {
            opacity: 0.84;
        }

        .ecg-line {
            transform-origin: center;
            transform: scaleY(0.04);
            transition: transform 0.3s ease;
        }

        .progress-screen-container.is-active .ecg-line {
            animation: ecg-heartbeat 1.4s infinite ease-in-out;
        }

        .aperture-label {
            display: none;
        }
