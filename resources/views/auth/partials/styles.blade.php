    <style>
        :root {
            --dar-green: #166b3a;
            --dar-green-dark: #005326;
            --dar-yellow: #facc15;
            --text-dark: #111827;
            --text-muted: #6b7280;
            --border-soft: #dfe5e2;
            --font-body: 'Google Sans';
            --font-heading: 'Google Sans';
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Google Sans' !important;
            background: #f8faf9;
            overflow: hidden;
        }

        .login-page {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-bg {
    position: absolute;
    inset: 0;
    z-index: 0;
    overflow: hidden;
    pointer-events: none;
    background-image: url("{{ asset('images/login-bg.png') }}");
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-color: #f8faf9;
}

.login-bg::after {
    content: "";
    position: absolute;
    inset: 0;
    background: rgba(248, 250, 249, 0.08);
}

        .login-content {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 470px;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: -1.5rem;
        }

        .logo-slot {
            width: 145px;
            height: 112px;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-placeholder {
            width: 128px;
            height: 96px;
            border: 2px dashed rgba(22, 107, 58, 0.45);
            border-radius: 0.75rem;
            background: rgba(255, 255, 255, 0.78);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--dar-green);
            font-size: 0.75rem;
            font-weight: 900;
            letter-spacing: 0.12em;
            line-height: 1.3;
        }

        .logo-image {
            max-width: 145px;
            max-height: 112px;
            object-fit: contain;
            display: block;
        }

        .login-card {
            width: 100%;
            background: rgba(255, 255, 255, 0.97);
            border: 1px solid var(--border-soft);
            border-radius: 0.7rem;
            box-shadow: 0 24px 65px rgba(15, 23, 42, 0.15);
            padding: 2.25rem;
        }

        .login-title {
    margin: 0;
    text-align: center;
    font-family: 'Google Sans' !important;
    font-size: 2.15rem;
    line-height: 1;
    font-weight: 800;
    letter-spacing: 0.18em;
    color: var(--text-dark);
    text-shadow:
        0.35px 0 0 currentColor,
        -0.35px 0 0 currentColor;
}

.login-subtitle,
.form-label, .login-office {
    font-family: 'Google Sans' !important;
}
        .login-subtitle {
            margin-top: 0.9rem;
            text-align: center;
            font-size: 0.82rem;
            color: #374151;
        }

        .login-office {
            margin-top: 0.25rem;
            text-align: center;
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        .login-form {
            margin-top: 2rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.92rem;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 0.45rem;
        }

        .form-input {
            width: 100%;
            border: 1px solid #cbd5d1;
            border-radius: 0.45rem;
            padding: 0.85rem 0.95rem;
            font-size: 0.92rem;
            color: var(--text-dark);
            background: #ffffff;
            outline: none;
            transition: 150ms ease;
        }

        .form-input:focus {
            border-color: #15803d;
            box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.14);
        }
        .password-field {
    position: relative;
}

.password-field .form-input {
    padding-right: 3rem;
}

.password-toggle {
    position: absolute;
    top: 50%;
    right: 0.85rem;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    color: #6b7280;
    cursor: pointer;
    padding: 0.25rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.password-toggle:hover {
    color: #166534;
}

.password-toggle svg {
    width: 1.25rem;
    height: 1.25rem;
}

        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
            font-size: 0.82rem;
        }

        .remember-label {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    color: #4b5563;
    cursor: pointer;
    user-select: none;
}

.remember-label {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    color: #4b5563;
    cursor: pointer;
    user-select: none;
}

.remember-checkbox {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.remember-control {
    width: 1.05rem;
    height: 1.05rem;
    border: 1.8px solid #9ca3af;
    border-radius: 999px;
    background: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: 150ms ease;
    flex-shrink: 0;
}

.remember-control::after {
    content: "";
    width: 0.45rem;
    height: 0.45rem;
    border-radius: 999px;
    background: #ffffff;
    transform: scale(0);
    transition: 120ms ease;
}

.remember-label:hover .remember-control {
    border-color: #166534;
}

.remember-checkbox:checked + .remember-control {
    background: #166534;
    border-color: #166534;
}

.remember-checkbox:checked + .remember-control::after {
    transform: scale(1);
}

.remember-checkbox:focus + .remember-control,
.remember-checkbox:focus-visible + .remember-control {
    border-color: #166534;
    box-shadow: 0 0 0 3px rgba(22, 101, 52, 0.18);
}

.remember-checkbox:active + .remember-control,
.remember-checkbox:checked:active + .remember-control {
    background: #166534;
    border-color: #166534;
}

        .forgot-link,
        .registration-link {
            color: #166534;
            font-weight: 800;
            text-decoration: none;
        }

        .forgot-link:hover,
        .registration-link:hover {
            text-decoration: underline;
        }

        .login-button {
            width: 100%;
            border: none;
            border-radius: 0.45rem;
            background: #006b2e;
            color: #ffffff;
            padding: 0.9rem 1rem;
            font-size: 0.95rem;
            font-weight: 900;
            cursor: pointer;
            transition: 150ms ease;
        }

        .login-button:hover {
            background: var(--dar-green-dark);
        }

        .login-footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.78rem;
            line-height: 1.5;
            color: #9ca3af;
        }

        .error-box {
            margin-top: 1.25rem;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            border-radius: 0.5rem;
            padding: 0.85rem;
            font-size: 0.85rem;
        }

        @media (max-width: 768px) {
            body {
                overflow: auto;
            }

            .login-page {
                align-items: flex-start;
                padding: 2rem 1rem;
            }

            .login-content {
                margin-top: 0;
            }

            .login-card {
                padding: 1.5rem;
            }

            .login-title {
                font-size: 1.55rem;
                letter-spacing: 0.16em;
            }

            .logo-slot {
                width: 120px;
                height: 92px;
                margin-bottom: 1.25rem;
            }

            .logo-placeholder {
                width: 112px;
                height: 82px;
            }
        }
    </style>
