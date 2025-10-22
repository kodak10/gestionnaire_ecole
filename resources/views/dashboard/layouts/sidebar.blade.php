<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu" style="min-height:100vh">
            @php
                $user = Auth::user();
                $ecole = $user ? $user->ecole : null;
            @endphp
            <ul>
                <li>
                    <a href="javascript:void(0);" class="d-flex align-items-center border bg-white rounded p-2 mb-4">
                        <img src="{{ $ecole && $ecole->logo ? asset($ecole->logo) : 'assets/img/icons/global-img.svg' }}" 
                             class="avatar avatar-md img-fluid rounded" 
                             alt="Logo {{ $ecole->nom ?? 'Ecole' }}">
                        <span class="text-dark ms-2 fw-normal">{{ $ecole->nom ?? 'Ecole non définie' }}</span>
                    </a>
                </li>
            </ul>

            <ul>
                <li>
                    <h6 class="submenu-hdr"><span>Main</span></h6>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs('dashboard') ? 'active subdrop' : '' }}">
                                <i class="ti ti-home"></i><span>Dashboard</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Tableau de Bord</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>
                
                <li>
                    <h6 class="submenu-hdr"><span>GESTIONS</span></h6>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs('eleves*') ? 'active subdrop' : '' }}">
                                <i class="ti ti-users"></i><span>Eleves</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                @hasanyrole('SuperAdministrateur|Administrateur')
                                <li><a href="{{ route('eleves.create') }}" class="{{ request()->routeIs('eleves.create') ? 'active' : '' }}">Inscription</a></li>
                                @endhasanyrole
                                @hasanyrole('SuperAdministrateur|Administrateur')
                                <li><a href="{{ route('preinscriptions.index') }}" class="{{ request()->routeIs('preinscriptions.index') ? 'active' : '' }}">PréInscription</a></li>
                                @endhasanyrole
                                @hasanyrole('SuperAdministrateur|Administrateur')
                                <li><a href="{{ route('reinscriptions.index') }}" class="{{ request()->routeIs('reinscriptions.index') ? 'active' : '' }}">RéInscription</a></li>
                                @endhasanyrole
                                <li><a href="{{ route('eleves.index') }}" class="{{ request()->routeIs('eleves.index') ? 'active' : '' }}">Liste des Eleves</a></li>
                            </ul>
                        </li>

                         <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs('notes*') ? 'active subdrop' : '' }}">
                                <i class="ti ti-users"></i><span>Moyennes</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                @hasanyrole('SuperAdministrateur|Administrateur|Directeur|Enseignant')
                                <li><a href="{{ route('notes.index') }}" class="{{ request()->routeIs('notes.index') ? 'active' : '' }}">Saisie de Moyenne</a></li>
                                @endhasanyrole
                                @hasanyrole('SuperAdministrateur|Administrateur|Directeur')
                                <li><a href="#" class="{{ request()->routeIs('notes.tableau') ? 'active' : '' }}">Tableau d'honneur</a></li>
                                @endhasanyrole
                            </ul>
                        </li>
                        
                        @hasanyrole('SuperAdministrateur|Administrateur|Caissiere')
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs(['scolairite*', 'reglements*']) ? 'active subdrop' : '' }}">
                                <i class="ti ti-wallet"></i><span>Scolarités</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                @hasanyrole('SuperAdministrateur|Administrateur')
                                <li><a href="{{ route('scolarite.index') }}" class="{{ request()->routeIs('scolarite.index*') ? 'active' : '' }}">Scolarités</a></li>
                                @endhasanyrole
                                <li><a href="{{ route('reglements.index') }}" class="{{ request()->routeIs('reglements.index*') ? 'active' : '' }}">Règlements</a></li>
                            </ul>
                        </li>
                        @endhasanyrole
                        
                        @hasanyrole('SuperAdministrateur|Administrateur|Caissiere')
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs('cantine*') ? 'active subdrop' : '' }}">
                                <i class="ti ti-coffee"></i><span>Cantine</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('cantine.index') }}" class="{{ request()->routeIs('cantine.index') ? 'active' : '' }}">Règlements</a></li>
                            </ul>
                        </li>
                        @endhasanyrole
                        
                        @hasanyrole('SuperAdministrateur|Administrateur|Caissiere')
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs('transport*') ? 'active subdrop' : '' }}">
                                <i class="ti ti-truck"></i><span>Transport</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('transport.index') }}" class="{{ request()->routeIs('transport.index') ? 'active' : '' }}">Règlements</a></li>
                            </ul>
                        </li>
                        @endhasanyrole
                        
                        @hasanyrole('SuperAdministrateur|Administrateur')
                        <li><a href="{{ route('relance.index') }}" class="{{ request()->routeIs('relance.index') ? 'active' : '' }}"><i class="ti ti-bell"></i><span>Relances</span></a></li>
                        @endhasanyrole
                        
                        @hasanyrole('SuperAdministrateur|Administrateur|Caissiere')
                        <li><a href="{{ route('depenses.index') }}" class="{{ request()->routeIs('depenses.index') ? 'active' : '' }}"><i class="ti ti-credit-card"></i><span>Depenses</span></a></li>
                        @endhasanyrole
                        
                        @hasanyrole('SuperAdministrateur|Administrateur|Directeur')
                        <li><a href="#" class=""><i class="ti ti-mail"></i><span>Messages</span></a></li>
                        @endhasanyrole
                    </ul>
                </li>

                @hasanyrole('SuperAdministrateur|Administrateur|Directeur|Caissiere')
                <li>
                    <h6 class="submenu-hdr"><span>Rapports</span></h6>
                    <ul>
                        @hasanyrole('SuperAdministrateur|Administrateur|Caissiere')
                        <li><a href="{{ route('journal-paiements.index') }}" class="{{ request()->routeIs('journal-paiements.index') ? 'active' : '' }}"><i class="ti ti-file-text"></i><span>Journal de Caisse</span></a></li>
                        @endhasanyrole
                        
                        @hasanyrole('SuperAdministrateur|Administrateur')
                        <li><a href="#" class=""><i class="ti ti-bar-chart"></i><span>Bilan Financier</span></a></li>
                        @endhasanyrole

                        @hasanyrole('SuperAdministrateur|Administrateur|Directeur')
                        <li><a href="#" class=""><i class="ti ti-bar-chart"></i><span>Bilan Scolaire</span></a></li>
                        @endhasanyrole
                        
                        @hasanyrole('SuperAdministrateur|Administrateur|Directeur')
                        <li><a href="#" class=""><i class="ti ti-archive"></i><span>Archivages</span></a></li>
                        @endhasanyrole

                        @hasanyrole('SuperAdministrateur|Administrateur|Directeur')
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs('documents*') ? 'active subdrop' : '' }}">
                                <i class="ti ti-files"></i><span>Documents</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li>
                                    <a class="{{ request()->routeIs('documents.inscriptions*') ? 'active' : '' }}" href="{{ route('documents.inscriptions') }}">
                                        <i class="ti ti-file-text"></i>
                                        <span>Fiches Inscription</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="{{ request()->routeIs('documents.certificats-scolarite*') ? 'active' : '' }}" href="{{ route('documents.certificats-scolarite') }}">
                                        <i class="ti ti-certificate"></i>
                                        <span>Certificats Scolarité</span>
                                    </a>
                                </li>
                                {{-- <li>
                                    <a href="#">
                                        <i class="ti ti-calendar"></i>
                                        <span>Certificats de Frequentation</span>
                                    </a>
                                </li> --}}
                                <li>
                                    <a class="{{ request()->routeIs('documents.fiches-presence*') ? 'active' : '' }}" href="{{ route('documents.fiches-presence') }}">
                                        <i class="ti ti-calendar"></i>
                                        <span>Fiches de Présence</span>
                                    </a>
                                </li>
                                {{-- <li><a href="#" class="">Carte d'Eleves</a></li> --}}
                            </ul>
                        </li>
                        @endhasanyrole
                    </ul>
                </li>
                @endhasanyrole

                

                <li>
                    <h6 class="submenu-hdr"><span>Paramètrages</span></h6>
                    <ul>
                        @hasanyrole('SuperAdministrateur')
                        <li><a href="{{ route('ecoles.index') }}" class="{{ request()->routeIs('ecoles.index') ? 'active' : '' }}"><i class="ti ti-building"></i><span>Etablissement</span></a></li>
                        @endhasanyrole
                        
                        @hasanyrole('SuperAdministrateur|Administrateur|Directeur')
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs(['classes*', 'matieres*', 'mentions*']) ? 'active subdrop' : '' }}">
                                <i class="ti ti-book"></i><span>Scolaires</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('classes.index') }}" class="{{ request()->routeIs('classes.index') ? 'active' : '' }}">Classes</a></li>
                                <li><a href="{{ route('enseignants.index') }}" class="{{ request()->routeIs('enseignants.index') ? 'active' : '' }}">Enseignants</a></li>
                                <li><a href="{{ route('matieres.index') }}" class="{{ request()->routeIs('matieres.index') ? 'active' : '' }}">Disciplines</a></li>
                                <li><a href="{{ route('mentions.index') }}" class="{{ request()->routeIs('mentions.index') ? 'active' : '' }}">Critères de Notation</a></li>
                            </ul>
                        </li>
                        @endhasanyrole

                        @hasanyrole('SuperAdministrateur')
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs(['tarifs*', 'tarifs-mensuels.index']) ? 'active subdrop' : '' }}">
                                <i class="ti ti-cash"></i><span>Scolarités</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a href="{{ route('tarifs.index') }}" class="{{ request()->routeIs('tarifs.index') ? 'active' : '' }}">Frais de Scolarité</a></li>
                                <li><a href="{{ route('tarifs-mensuels.index') }}" class="{{ request()->routeIs('tarifs-mensuels.index') ? 'active' : '' }}">Parametrage de Règlement</a></li>
                            </ul>
                        </li>
                        @endhasanyrole
                        
                        <li class="submenu">
                            <a href="javascript:void(0);" class="{{ request()->routeIs(['profile', 'users*']) ? 'active subdrop' : '' }}">
                                <i class="ti ti-user"></i><span>Compte</span><span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li>
                                    <a href="{{ route('profile') }}" class="{{ request()->routeIs('profile') ? 'active' : '' }}">Mon Profil</a>
                                </li>
                                <li>
                                    <a href="#" class="">Mon Activité</a>
                                </li>
                                @hasanyrole('SuperAdministrateur')
                                    <li><a href="{{ route('users.index') }}" class="">Utilisateurs</a></li>
                                    <li>
                                        <a href="#" class="">Mouchard</a>
                                    </li>
                                @endhasanyrole
                            </ul>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>