<?php
session_start();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="icon" type="image/png" href="../ico1.ico" />

<title>L2 Virtual Boost — Acesso ao Reino</title>

<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

/* =========================================================
   VARIABLES
========================================================= */

:root{

    --blue:#1976d2;
    --blue-light:#4fc3f7;
    --blue-bright:#00bfff;
    --blue-dark:#06152b;

    --cyan:#00d9ff;

    --silver:#c9d6e5;
    --silver-light:#eef7ff;

    --bg:#020710;

}

/* =========================================================
   RESET
========================================================= */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

html{
    scroll-behavior:smooth;
}

body{

    background:
        #020710;

    color:#e7f2ff;

    font-family:'Rajdhani',sans-serif;

    min-height:100vh;

    overflow-x:hidden;

}

/* =========================================================
   DARK FANTASY BACKGROUND
========================================================= */

.bg-fx{

    position:fixed;

    inset:0;

    z-index:-5;

    overflow:hidden;

    background:

        linear-gradient(
            180deg,
            rgba(1,7,18,.72),
            rgba(1,7,18,.94)
        ),

        radial-gradient(
            circle at 50% 30%,
            rgba(0,119,255,.20),
            transparent 48%
        ),

        radial-gradient(
            circle at 15% 80%,
            rgba(0,191,255,.07),
            transparent 40%
        ),

        url('../img/lineage-bg.jpg');

    background-size:cover;

    background-position:center;

    background-attachment:fixed;

}

/* =========================================================
   DARK OVERLAY
========================================================= */

.bg-fx::after{

    content:'';

    position:absolute;

    inset:0;

    background:

        radial-gradient(
            ellipse at center,
            transparent 10%,
            rgba(0,0,0,.45) 65%,
            rgba(0,0,0,.88) 100%
        );

}

/* =========================================================
   GRID
========================================================= */

.grid-fx{

    position:fixed;

    inset:0;

    z-index:-3;

    pointer-events:none;

    background-image:

        repeating-linear-gradient(
            0deg,
            transparent,
            transparent 100px,
            rgba(79,195,247,.025) 100px,
            rgba(79,195,247,.025) 101px
        ),

        repeating-linear-gradient(
            90deg,
            transparent,
            transparent 100px,
            rgba(79,195,247,.025) 100px,
            rgba(79,195,247,.025) 101px
        );

    opacity:.35;

    animation:gridPulse 8s ease-in-out infinite;

}

@keyframes gridPulse{

    0%,100%{
        opacity:.20;
    }

    50%{
        opacity:.42;
    }

}

/* =========================================================
   BLUE PARTICLES
========================================================= */

.fire-particles{

    position:fixed;

    inset:0;

    z-index:-1;

    pointer-events:none;

    overflow:hidden;

}

.ember{

    position:absolute;

    bottom:-20px;

    width:3px;

    height:3px;

    border-radius:50%;

    background:

        radial-gradient(
            circle,
            #ffffff,
            var(--blue-bright),
            #0756a8
        );

    box-shadow:

        0 0 8px var(--blue-bright),
        0 0 18px rgba(0,191,255,.65);

    animation:emberRise linear infinite;

}

@keyframes emberRise{

    0%{

        transform:
            translateY(0)
            translateX(0);

        opacity:0;

    }

    15%{
        opacity:1;
    }

    100%{

        transform:
            translateY(-100vh)
            translateX(45px);

        opacity:0;

    }

}

/* =========================================================
   NAVBAR
========================================================= */

.navbar{

    position:fixed;

    top:0;
    left:0;
    right:0;

    z-index:50;

    display:flex;

    justify-content:space-between;

    align-items:center;

    padding:18px 6vw;

    backdrop-filter:blur(15px);

    background:

        linear-gradient(
            180deg,
            rgba(2,7,16,.96),
            rgba(2,7,16,.60)
        );

    border-bottom:

        1px solid
        rgba(0,191,255,.25);

    box-shadow:

        0 4px 35px
        rgba(0,119,255,.18);

}

/* =========================================================
   LOGO
========================================================= */

.logo{

    font-family:'Orbitron',sans-serif;

    font-weight:900;

    font-size:1.3rem;

    letter-spacing:1px;

    text-decoration:none;

    color:#dceeff;

    text-shadow:

        0 0 8px rgba(79,195,247,.7),
        0 0 18px rgba(0,119,255,.35);

    transition:.3s;

}

.logo span{

    color:var(--blue-light);

    text-shadow:

        0 0 10px
        rgba(0,191,255,.8);

}

.logo:hover{

    color:#fff;

    text-shadow:

        0 0 12px
        var(--blue-bright),

        0 0 30px
        rgba(0,119,255,.7);

}

/* =========================================================
   NAV LINKS
========================================================= */

.nav-links{

    display:flex;

    align-items:center;

    gap:28px;

}

.nav-links a{

    position:relative;

    color:#a9b8ca;

    text-decoration:none;

    font-family:'Orbitron',sans-serif;

    font-size:.75rem;

    font-weight:700;

    letter-spacing:1px;

    text-transform:uppercase;

    transition:.3s;

}

.nav-links a::after{

    content:'';

    position:absolute;

    left:0;

    bottom:-7px;

    width:0;

    height:2px;

    background:

        linear-gradient(
            90deg,
            var(--blue),
            var(--cyan)
        );

    box-shadow:

        0 0 10px
        var(--blue-bright);

    transition:.3s;

}

.nav-links a:hover{

    color:var(--blue-light);

    text-shadow:

        0 0 12px
        rgba(0,191,255,.7);

}

.nav-links a:hover::after{

    width:100%;

}

/* =========================================================
   MAIN
========================================================= */

.main-container{

    min-height:100vh;

    display:flex;

    justify-content:center;

    align-items:center;

    padding:
        120px 20px
        60px;

    position:relative;

}

/* =========================================================
   ACCESS BOX
========================================================= */

.box{

    width:100%;

    max-width:450px;

    position:relative;

    padding:36px 34px;

    background:

        linear-gradient(
            145deg,
            rgba(8,22,42,.94),
            rgba(2,7,16,.97)
        );

    border:

        1px solid
        rgba(79,195,247,.30);

    border-radius:12px;

    box-shadow:

        0 25px 80px
        rgba(0,0,0,.85),

        0 0 45px
        rgba(0,119,255,.16),

        inset 0 0 35px
        rgba(0,191,255,.025);

    backdrop-filter:blur(16px);

    overflow:hidden;

    animation:
        boxAppear .8s
        cubic-bezier(.2,.7,.2,1);

}

/* TOP LIGHT */

.box::before{

    content:'';

    position:absolute;

    top:0;

    left:0;

    right:0;

    height:2px;

    background:

        linear-gradient(
            90deg,
            transparent,
            var(--blue),
            var(--cyan),
            var(--blue),
            transparent
        );

    box-shadow:

        0 0 20px
        var(--blue-bright);

}

/* INNER GLOW */

.box::after{

    content:'';

    position:absolute;

    width:300px;

    height:300px;

    top:-170px;

    left:50%;

    transform:
        translateX(-50%);

    background:

        radial-gradient(
            circle,
            rgba(0,191,255,.13),
            transparent 70%
        );

    pointer-events:none;

}

@keyframes boxAppear{

    from{

        opacity:0;

        transform:
            translateY(30px)
            scale(.97);

    }

    to{

        opacity:1;

        transform:none;

    }

}

/* =========================================================
   BOX HEADER
========================================================= */

.box-header{

    text-align:center;

    margin-bottom:25px;

    position:relative;

    z-index:1;

}

.box-icon{

    font-size:2.3rem;

    margin-bottom:10px;

    filter:

        drop-shadow(
            0 0 15px
            rgba(0,191,255,.75)
        );

}

.box-header h1{

    font-family:'Orbitron',sans-serif;

    font-size:1.35rem;

    text-transform:uppercase;

    letter-spacing:2px;

    background:

        linear-gradient(
            180deg,
            #ffffff,
            #9edfff,
            #2589c9
        );

    -webkit-background-clip:text;

    background-clip:text;

    color:transparent;

    filter:

        drop-shadow(
            0 0 12px
            rgba(0,191,255,.35)
        );

}

.box-header p{

    margin-top:7px;

    color:#7f9bb7;

    font-size:.85rem;

    letter-spacing:1px;

}

/* =========================================================
   TABS
========================================================= */

.tabs{

    display:flex;

    gap:6px;

    margin-bottom:25px;

    padding:5px;

    border:

        1px solid
        rgba(79,195,247,.12);

    border-radius:8px;

    background:

        rgba(1,7,16,.78);

}

.tab-btn{

    flex:1;

    background:transparent;

    border:none;

    color:#71859b;

    font-family:'Orbitron',sans-serif;

    font-size:.68rem;

    font-weight:700;

    padding:11px 6px;

    cursor:pointer;

    border-radius:5px;

    transition:.3s;

    text-transform:uppercase;

    letter-spacing:.5px;

}

.tab-btn:hover{

    color:#bceeff;

    background:

        rgba(0,191,255,.08);

}

.tab-btn.active{

    color:#fff;

    background:

        linear-gradient(
            180deg,
            #147ac1,
            #064c82
        );

    box-shadow:

        0 0 18px
        rgba(0,119,255,.38),

        inset 0 1px
        rgba(255,255,255,.15);

    text-shadow:

        0 0 8px
        rgba(79,195,247,.6);

}

/* =========================================================
   FORMS
========================================================= */

.form-section{

    display:none;

    position:relative;

    z-index:1;

    animation:
        formFade .35s ease;

}

.form-section.active{

    display:block;

}

@keyframes formFade{

    from{

        opacity:0;

        transform:
            translateY(8px);

    }

    to{

        opacity:1;

        transform:none;

    }

}

/* =========================================================
   LABELS
========================================================= */

label{

    display:block;

    font-size:.75rem;

    color:#7fc9ec;

    margin-bottom:7px;

    font-weight:700;

    text-transform:uppercase;

    letter-spacing:1px;

}

/* =========================================================
   INPUTS
========================================================= */

input{

    width:100%;

    padding:13px 14px;

    margin-bottom:17px;

    background:

        rgba(1,5,12,.88);

    border:

        1px solid
        rgba(79,195,247,.20);

    border-radius:7px;

    color:#fff;

    font-family:'Rajdhani',sans-serif;

    font-size:1rem;

    outline:none;

    transition:.3s;

    box-shadow:

        inset 0 0 14px
        rgba(0,0,0,.6);

}

input:hover{

    border-color:

        rgba(0,191,255,.45);

}

input:focus{

    border-color:
        var(--cyan);

    box-shadow:

        0 0 14px
        rgba(0,191,255,.15),

        inset 0 0 12px
        rgba(0,0,0,.5);

}

/* =========================================================
   SUBMIT BUTTON
========================================================= */

button.submit-btn{

    position:relative;

    width:100%;

    padding:14px;

    margin-top:3px;

    border:

        1px solid
        rgba(0,191,255,.65);

    border-radius:7px;

    background:

        linear-gradient(
            180deg,
            rgba(0,119,255,.20),
            rgba(0,63,120,.12)
        );

    color:#eaf9ff;

    font-family:'Orbitron',sans-serif;

    font-size:.82rem;

    font-weight:700;

    letter-spacing:1px;

    text-transform:uppercase;

    cursor:pointer;

    transition:.35s;

    box-shadow:

        0 0 20px
        rgba(0,119,255,.18),

        inset 0 0 15px
        rgba(0,191,255,.04);

    overflow:hidden;

}

button.submit-btn::before{

    content:'';

    position:absolute;

    inset:0;

    background:

        linear-gradient(
            90deg,
            transparent,
            rgba(255,255,255,.18),
            transparent
        );

    transform:
        translateX(-100%);

    transition:.5s;

}

button.submit-btn:hover{

    color:#fff;

    border-color:
        var(--cyan);

    background:

        linear-gradient(
            180deg,
            rgba(0,145,220,.35),
            rgba(0,65,125,.18)
        );

    box-shadow:

        0 0 30px
        rgba(0,191,255,.35),

        0 0 55px
        rgba(0,119,255,.18);

    text-shadow:

        0 0 10px
        var(--blue-light);

}

button.submit-btn:hover::before{

    transform:
        translateX(100%);

}

/* =========================================================
   ALERT
========================================================= */

.alert{

    position:relative;

    z-index:2;

    background:

        linear-gradient(
            135deg,
            rgba(0,119,255,.15),
            rgba(0,191,255,.05)
        );

    border:

        1px solid
        rgba(0,191,255,.55);

    color:#bceeff;

    padding:11px 13px;

    border-radius:7px;

    font-size:.88rem;

    margin-bottom:18px;

    text-align:center;

    box-shadow:

        0 0 18px
        rgba(0,191,255,.10);

}

/* =========================================================
   FOOTER
========================================================= */

.box-footer{

    margin-top:24px;

    padding-top:16px;

    border-top:

        1px solid
        rgba(79,195,247,.10);

    text-align:center;

    color:#526b83;

    font-size:.72rem;

    letter-spacing:1px;

    text-transform:uppercase;

}

.box-footer span{

    color:#1677b7;

}

/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:820px){

    .navbar{

        padding:
            16px 20px;

    }

    .nav-links{

        display:none;

    }

    .logo{

        font-size:1rem;

    }

    .main-container{

        padding:
            105px 16px
            40px;

    }

    .box{

        max-width:430px;

        padding:
            30px 22px;

    }

    .tabs{

        gap:3px;

    }

    .tab-btn{

        font-size:.62rem;

    }

}

@media(max-width:420px){

    .box{

        padding:
            28px 18px;

    }

    .box-header h1{

        font-size:1.15rem;

    }

    .tab-btn{

        font-size:.58rem;

        padding:
            10px 3px;

    }

}

/* Fundo escuro cobrindo a tela */
.terms-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.terms-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(4px);
}

/* Janela central do modal */
.terms-window {
    position: relative;
    width: 90%;
    max-width: 600px;
    background: #0d1117;
    border: 1px: solid #30363d;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7);
    display: flex;
    flex-direction: column;
    max-height: 85vh;
    color: #c9d1d9;
    font-family: sans-serif;
}

/* Cabeçalho */
.terms-header {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    border-bottom: 1px solid #21262d;
    background: #161b22;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
}

.terms-icon {
    font-size: 28px;
}

.terms-header h2 {
    margin: 0;
    font-size: 18px;
    color: #fff;
}

.terms-header p {
    margin: 4px 0 0 0;
    font-size: 13px;
    color: #8b949e;
}

/* Conteúdo com scroll */
.terms-content {
    padding: 20px;
    overflow-y: auto;
    font-size: 14px;
    line-height: 1.6;
}

.terms-content h3 {
    margin-top: 0;
    color: #58a6ff;
}

.terms-content p {
    margin-bottom: 15px;
}

/* Rodapé e Botão */
.terms-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 20px;
    border-top: 1px solid #21262d;
    background: #161b22;
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
}

.terms-footer span {
    font-size: 12px;
    color: #8b949e;
}

.terms-footer button {
    background: #238636;
    color: #fff;
    border: none;
    padding: 10px 20px;
    font-weight: bold;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
}

.terms-footer button:hover {
    background: #2ea043;
}

</style>

</head>

<body>


<!-- ======================================================
     BACKGROUND
====================================================== -->

<div class="bg-fx"></div>

<div class="grid-fx"></div>

<div
    class="fire-particles"
    id="fireParticles">
</div>


<!-- ======================================================
     NAVBAR
====================================================== -->

<nav class="navbar">

    <a
        href="../index.html"
        class="logo">

        L2
        <span>VIRTUAL</span>
        BOOST

    </a>


    <div class="nav-links">

        <a href="../index.html">
            Servidor
        </a>

        <a href="../index.html#rates">
            Rates
        </a>

        <a href="../index.html#economy">
            Economia
        </a>

        <a href="../index.html#download">
            Download
        </a>

        <a href="../index.html#join">
            Discord
        </a>

    </div>

</nav>


<!-- ======================================================
     MAIN
====================================================== -->

<div class="main-container">


    <div class="box">


        <!-- HEADER -->

        <div class="box-header">

            <div class="box-icon">
                ⚔️
            </div>

            <h1>
                Acesso ao Reino
            </h1>

            <p>
                Entre ou crie sua conta
            </p>

        </div>


        <!-- TABS -->

        <div class="tabs">

            <button
                type="button"
                class="tab-btn active"
                onclick="switchTab('login', this)">

                Logar

            </button>


            <button
                type="button"
                class="tab-btn"
                onclick="switchTab('register', this)">

                Criar Conta

            </button>


            <button
                type="button"
                class="tab-btn"
                onclick="switchTab('recover', this)">

                Recuperar

            </button>

        </div>


        <!-- ALERT -->

        <?php if(isset($_SESSION['error'])): ?>

            <div class="alert">

                <?= $_SESSION['error']; unset($_SESSION['error']); ?>

            </div>

        <?php endif; ?>


        <!-- ==================================================
             LOGIN
        ================================================== -->

        <form
            id="form-login"
            class="form-section active"
            action="process_auth.php"
            method="POST">

            <input
                type="hidden"
                name="action"
                value="login">


            <label>
                Login / Usuário
            </label>

            <input
                type="text"
                name="login"
                required
                autocomplete="off">


            <label>
                Senha
            </label>

            <input
                type="password"
                name="password"
                required>


            <button
                type="submit"
                class="submit-btn">

                ⚔️ Entrar no Jogo

            </button>

        </form>


        <!-- ==================================================
             REGISTRO
        ================================================== -->

        <form
            id="form-register"
            class="form-section"
            action="process_auth.php"
            method="POST">

            <input
                type="hidden"
                name="action"
                value="register">


            <label>
                Novo Login
            </label>

            <input
                type="text"
                name="login"
                required
                autocomplete="off">


            <label>
                E-mail
            </label>

            <input
                type="email"
                name="email"
                required
                autocomplete="off">


            <label>
                Senha
            </label>

            <input
                type="password"
                name="password"
                required>


            <label>
                Confirmar Senha
            </label>

            <input
                type="password"
                name="password_confirm"
                required>


            <button
                type="submit"
                class="submit-btn">

                🔥 Criar Conta

            </button>

        </form>


        <!-- ==================================================
             RECUPERAÇÃO
        ================================================== -->

        <form
            id="form-recover"
            class="form-section"
            action="process_auth.php"
            method="POST">

            <input
                type="hidden"
                name="action"
                value="recover">


            <label>
                Seu Login
            </label>

            <input
                type="text"
                name="login"
                required
                autocomplete="off">


            <label>
                E-mail Cadastrado
            </label>

            <input
                type="email"
                name="email"
                required
                autocomplete="off">


            <button
                type="submit"
                class="submit-btn">

                🛡️ Recuperar Senha

            </button>

        </form>


        <!-- FOOTER -->

        <div class="box-footer">

            L2 Virtual Boost

            <span>•</span>

            Chronicle High Five

            <span>•</span>

            Low Rate

        </div>


    </div>

</div>


<script>

/* =========================================================
   TABS
========================================================= */

function switchTab(tabName, button){

    document
        .querySelectorAll('.form-section')
        .forEach(function(form){

            form.classList.remove('active');

        });


    document
        .querySelectorAll('.tab-btn')
        .forEach(function(btn){

            btn.classList.remove('active');

        });


    const selectedForm =
        document.getElementById(
            'form-' + tabName
        );


    if(selectedForm){

        selectedForm.classList.add('active');

    }


    if(button){

        button.classList.add('active');

    }

}


/* =========================================================
   BLUE PARTICLES
========================================================= */

const fireContainer =
    document.getElementById(
        'fireParticles'
    );


for(let i = 0; i < 55; i++){

    const ember =
        document.createElement('div');


    ember.className =
        'ember';


    ember.style.left =
        Math.random() * 100 + '%';


    ember.style.animationDuration =
        (Math.random() * 4 + 3) + 's';


    ember.style.animationDelay =
        (Math.random() * 6) + 's';


    ember.style.width =
        (Math.random() * 3 + 1) + 'px';


    ember.style.height =
        ember.style.width;


    fireContainer.appendChild(
        ember
    );

}

function acceptTerms() {
    // Esconde o modal
    const modal = document.getElementById('termsModal');
    if (modal) {
        modal.style.display = 'none';
    }
    
    // Salva no navegador para não exibir novamente na mesma sessão/visita
    localStorage.setItem('l2_terms_accepted', 'true');
}

// Opcional: Verifica ao carregar a página se o usuário já aceitou antes
window.addEventListener('DOMContentLoaded', () => {
    const accepted = localStorage.getItem('l2_terms_accepted');
    const modal = document.getElementById('termsModal');
    
    if (accepted === 'true' && modal) {
        modal.style.display = 'none';
    }
});

</script>
<div id="termsModal" class="terms-modal">
    <div class="terms-overlay"></div>
    <div class="terms-window">
        <div class="terms-header">
            <div class="terms-icon">⚔️</div>
            <div>
                <h2>Bem-vindo ao L2 Virtual Boost</h2>
                <p>Antes de entrar no reino, leia os termos abaixo.</p>
            </div>
        </div>

        <div class="terms-content">
            <h3>⚔️ Termos e Avisos</h3>
            <p>O <strong>L2 Virtual Boost</strong> é um servidor privado independente baseado em <strong>Lineage II High Five</strong>, administrado por sua própria equipe.</p>
            <p><strong>O L2 Virtual Boost não é afiliado, associado, patrocinado ou oficialmente vinculado à NCSoft.</strong></p>
            <p>Contas, personagens, itens, Adena, sistemas, registros e demais elementos existentes dentro do servidor estão sujeitos à administração e às regras estabelecidas pela equipe do Virtual Boost.</p>
            <p><strong>Nossa moeda principal é Adena. Este valor é atualizado em tempo real em nosso site a cada 5 segundos, mantendo o foco sempre em confiança e transparência com a nossa comunidade sobre a economia.</strong></p>
            <p>É proibido explorar <strong>bugs, exploits, vulnerabilidades, duplicações ou falhas</strong> para obter vantagem própria ou beneficiar terceiros.</p>
            <p>Tentativas de fraude, manipulação, exploração de falhas ou obtenção de vantagens indevidas poderão resultar em <strong>advertência, bloqueio, suspensão, remoção de itens ou banimento</strong>, conforme a gravidade da situação.</p>
            <p>O servidor poderá passar por <strong>fase Beta</strong>, durante a qual poderão ocorrer testes, alterações, manutenções, correções, instabilidades ou ajustes de sistemas.</p>
            <p><strong>Durante a Beta, todos os dados, personagens, itens, progresso ou sistemas serão mantidos, com segurança para preservar a progressão e garantir a credibilidade do Virtual Boost.</strong></p>
            <p>A administração poderá realizar alterações em <strong>rates, economia, sistemas, eventos, mecânicas e regras</strong> visando o equilíbrio, segurança e evolução do servidor.</p>
            <p>Estamos trabalhando seriamente no projeto e somos fãs e admiradores do Lineage tanto quanto vocês. Portanto, estamos firmando nosso compromisso em sempre atender, oferecer suporte e presença sempre que necessário, visando o bem-estar, experiência e total imparcialidade, mantendo nosso foco em não ser <strong>"pay to win"</strong>.</p>
            <p>Qualquer problema não hesite em procurar o suporte através do site. Também pode entrar em contato via mensagem pela nossa Página Oficial do Facebook.</p>
            <p>Ao continuar, você declara que leu e compreendeu estas informações e concorda com as regras de utilização do L2 Virtual Boost.</p>
        </div>

        <div class="terms-footer">
            <span>L2 Virtual Boost • High Five • Low Rate</span>
            <button type="button" onclick="acceptTerms()">⚔️ LI E ACEITO</button>
        </div>
    </div>
</div>
</body>

</html>