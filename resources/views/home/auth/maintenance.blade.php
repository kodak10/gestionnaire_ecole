@extends('home.layout.app')

@section('content')
<div class="container-fuild">
    <div class="w-100 overflow-hidden position-relative flex-wrap d-block vh-100">
        <div class="row">
            <div class="col-lg-6">
                <div class="login-background position-relative d-lg-flex align-items-center justify-content-center d-lg-block d-none flex-wrap vh-100 overflowy-auto">
                    <div>
                        <img src="{{ asset('assets/img/authentication/authentication-02.jpg') }}" alt="Img">
                    </div>
                    <div class="authen-overlay-item w-100 p-4">
                        <div class="text-center mb-4">
                            <div class="display-1 text-white mb-3">
                                <i class="ti ti-tools"></i>
                            </div>
                            <h2 class="text-white mb-3">En Maintenance</h2>
                            <p class="text-white-50 mb-0">Nous travaillons dur pour améliorer votre expérience</p>
                        </div>
                        <div class="d-flex align-items-center flex-row mb-3 justify-content-between p-3 br-5 gap-3 card">
                            <div>
                                <h6>🚀 Améliorations</h6>
                                <p class="mb-0 text-truncate">Nouvelles fonctionnalités en cours de développement.</p>
                            </div>
                            <a href="javascript:void(0);"><i class="ti ti-chevrons-right"></i></a>
                        </div>
                        <div class="d-flex align-items-center flex-row mb-3 justify-content-between p-3 br-5 gap-3 card">
                            <div>
                                <h6>⚡ Optimisation</h6>
                                <p class="mb-0 text-truncate">Performance et rapidité améliorées.</p>
                            </div>
                            <a href="javascript:void(0);"><i class="ti ti-chevrons-right"></i></a>
                        </div>
                        <div class="d-flex align-items-center flex-row mb-3 justify-content-between p-3 br-5 gap-3 card">
                            <div>
                                <h6>🛡️ Sécurité</h6>
                                <p class="mb-0 text-truncate">Protection renforcée de vos données.</p>
                            </div>
                            <a href="javascript:void(0);"><i class="ti ti-chevrons-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12 col-sm-12">
                <div class="row justify-content-center align-items-center vh-100 overflow-auto flex-wrap">
                    <div class="col-md-8 mx-auto p-4">
                        <div>
                            <div class="mx-auto mb-3 text-center">
                                <div class="display-1 mb-3">
                                    <i class="ti ti-construction text-warning"></i>
                                </div>
                                <h3 class="mt-3">{{ config('app.name', 'OptiScolaire') }}</h3>
                                <p class="text-muted">Site en maintenance</p>
                            </div>
                            <div class="card">
                                <div class="card-body p-4">
                                    <div class="mb-4 text-center">
                                        <div class="display-4 mb-3">
                                            <i class="ti ti-clock"></i>
                                        </div>
                                        <h2 class="mb-2">Nous revenons bientôt</h2>
                                        <p class="mb-0 text-muted">Notre équipe technique travaille actuellement sur l'amélioration de la plateforme.</p>
                                        
                                    </div>

                                    <div class="mb-4">
                                        
                                        <div class="d-flex justify-content-center gap-4 mb-3">
                                            <div class="text-center">
                                                <div class="h1 mb-0 fw-bold" id="hours">00</div>
                                                <small class="text-muted">Heures</small>
                                            </div>
                                            <div class="text-center">
                                                <div class="h1 mb-0 fw-bold" id="minutes">00</div>
                                                <small class="text-muted">Minutes</small>
                                            </div>
                                            <div class="text-center">
                                                <div class="h1 mb-0 fw-bold" id="seconds">00</div>
                                                <small class="text-muted">Secondes</small>
                                            </div>
                                        </div>
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                 role="progressbar" 
                                                 style="width: 75%; background: linear-gradient(90deg, #0d6efd, #0dcaf0);">
                                            </div>
                                        </div>
                                    </div>


                                    <div class="text-center">
                                        <a href="tel:+2250103810998" class="btn btn-outline-primary w-100">
                                            <i class="ti ti-phone me-2"></i>
                                            Contacter le support
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 text-center">
                                <div class="d-flex justify-content-center gap-3 mb-2">
                                    <a href="#" class="text-muted"><i class="ti ti-brand-facebook fs-5"></i></a>
                                    <a href="#" class="text-muted"><i class="ti ti-brand-twitter fs-5"></i></a>
                                    <a href="#" class="text-muted"><i class="ti ti-brand-linkedin fs-5"></i></a>
                                    <a href="#" class="text-muted"><i class="ti ti-brand-youtube fs-5"></i></a>
                                </div>
                                <p class="mb-0 small">Copyright &copy; {{ date('Y') }} - {{ config('app.name', 'OptiScolaire') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Timer de maintenance (compte à rebours exemple)
        let totalSeconds = 72000; // 2 heures
        const hoursEl = document.getElementById('hours');
        const minutesEl = document.getElementById('minutes');
        const secondsEl = document.getElementById('seconds');

        function updateTimer() {
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;

            hoursEl.textContent = String(hours).padStart(2, '0');
            minutesEl.textContent = String(minutes).padStart(2, '0');
            secondsEl.textContent = String(seconds).padStart(2, '0');

            if (totalSeconds > 0) {
                totalSeconds--;
            }
        }

        updateTimer();
        setInterval(updateTimer, 1000);

        // Fonction de notification
        window.notifyMe = function() {
            const email = document.getElementById('notifyEmail').value;
            if (!email) {
                alert('Veuillez entrer votre email');
                return;
            }
            if (!email.includes('@')) {
                alert('Veuillez entrer un email valide');
                return;
            }
            alert('📧 Vous serez notifié à ' + email + ' dès la fin de la maintenance !');
            document.getElementById('notifyEmail').value = '';
        };

        // Animation du compteur
        const counterItems = document.querySelectorAll('.h1');
        counterItems.forEach((item, index) => {
            item.style.animation = `fadeInUp 0.5s ease ${index * 0.1}s both`;
        });
    });
</script>

<style>
    /* Styles additionnels */
    .alert-warning {
        background-color: rgba(255, 193, 7, 0.1);
        border-color: rgba(255, 193, 7, 0.2);
        color: #856404;
    }

    .display-1 {
        font-size: 4rem;
    }

    .progress-bar-animated {
        animation: progress-bar-stripes 1s linear infinite;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .card {
        transition: transform 0.3s ease;
        border: none;
        box-shadow: 0 0 30px rgba(0,0,0,0.05);
    }

    .card:hover {
        transform: translateY(-5px);
    }

    .btn-primary {
        background: linear-gradient(135deg, #0d6efd, #0dcaf0);
        border: none;
    }

    .btn-primary:hover {
        transform: scale(1.02);
        box-shadow: 0 5px 20px rgba(13, 110, 253, 0.3);
    }

    .btn-outline-primary {
        border-color: #0d6efd;
        color: #0d6efd;
    }

    .btn-outline-primary:hover {
        background: #0d6efd;
        color: white;
    }

    .h1 {
        font-weight: 700;
        color: #0d6efd;
        font-size: 2.5rem;
    }

    .social-links a {
        transition: all 0.3s ease;
        display: inline-block;
    }

    .social-links a:hover {
        transform: translateY(-3px);
        color: #0d6efd !important;
    }

    .input-group .form-control:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
    }

    @media (max-width: 768px) {
        .display-1 {
            font-size: 3rem;
        }
        .h1 {
            font-size: 2rem;
        }
        .card-body {
            padding: 1.5rem !important;
        }
    }

    @media (max-width: 576px) {
        .display-1 {
            font-size: 2.5rem;
        }
        .h1 {
            font-size: 1.5rem;
        }
    }
</style>
@endsection