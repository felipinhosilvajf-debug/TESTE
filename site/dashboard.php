<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_login'])) {
    header("Location: login.php");
    exit;
}

$loginUsuario = $_SESSION['user_login'];

$host = '127.0.0.1';
$port = '3306';
$db   = 'l2mythras';
$user = 'root';
$pass = 'mas3510';


/* =========================================================
   CLASSES — LINEAGE 2 HIGH FIVE
========================================================= */

$class_names = [

    0   => 'Human Fighter',

    88  => 'Duelist',
    89  => 'Dreadnought',
    90  => 'Phoenix Knight',
    91  => 'Hell Knight',
    92  => 'Sagittarius',
    93  => 'Adventurer',

    94  => 'Archmage',
    95  => 'Soultaker',
    96  => 'Arcana Lord',
    97  => 'Cardinal',
    98  => 'Hierophant',

    99  => "Eva's Templar",
    100 => 'Sword Muse',
    101 => 'Wind Rider',
    102 => 'Moonlight Sentinel',
    103 => 'Mystic Muse',
    104 => 'Elemental Master',
    105 => "Eva's Saint",

    106 => 'Shillien Knight',
    107 => 'Spectral Dancer',
    108 => 'Ghost Hunter',
    109 => 'Ghost Sentinel',
    110 => 'Storm Screamer',
    111 => 'Spectral Master',
    112 => 'Shillien Saint',

    113 => 'Titan',
    114 => 'Grand Khavatari',
    115 => 'Dominator',
    116 => 'Doomcryer',
    117 => 'Fortune Seeker',
    118 => 'Maestro',

    131 => 'Doombringer',
    132 => 'Male Soulhound',
    133 => 'Female Soulhound',
    134 => 'Trickster',
    135 => 'Judicator'
];


try {

    /* =====================================================
       DATABASE
    ===================================================== */

    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE =>
                PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE =>
                PDO::FETCH_ASSOC
        ]
    );


    /* =====================================================
       PERSONAGENS DA CONTA
    ===================================================== */

    $stmtChars = $pdo->prepare("

        SELECT
            obj_Id,
            char_name,
            karma,
            pvpkills,
            pkkills,
            clanid,
            title,
            rec_have,
            rec_left,
            online,
            onlinetime,
            pledge_rank,
            fame,
            vitality,
            raidkills

        FROM characters

        WHERE account_name = ?

        ORDER BY char_name ASC

    ");

    $stmtChars->execute([
        $loginUsuario
    ]);

    $personagens =
        $stmtChars->fetchAll();


    $dadosCompletos = [];


    foreach ($personagens as $char) {


        /* =================================================
           BASE CLASS / STATUS PRINCIPAL

           A tabela character_subclasses contém:
           - level
           - exp
           - sp
           - curHp
           - curMp
           - curCp
           - maxHp
           - maxMp
           - maxCp
           - class_id
           - isBase
        ================================================= */

        $stmtBase = $pdo->prepare("

            SELECT
                class_id,
                level,
                exp,
                sp,
                curHp,
                curMp,
                curCp,
                maxHp,
                maxMp,
                maxCp

            FROM character_subclasses

            WHERE char_obj_id = ?

            AND isBase = 1

            LIMIT 1

        ");

        $stmtBase->execute([
            $char['obj_Id']
        ]);

        $base =
            $stmtBase->fetch();


        /*
         * Caso o personagem não tenha uma linha
         * marcada como isBase = 1, pegamos a
         * primeira classe cadastrada.
         */

        if (!$base) {

            $stmtBase = $pdo->prepare("

                SELECT
                    class_id,
                    level,
                    exp,
                    sp,
                    curHp,
                    curMp,
                    curCp,
                    maxHp,
                    maxMp,
                    maxCp

                FROM character_subclasses

                WHERE char_obj_id = ?

                ORDER BY level DESC

                LIMIT 1

            ");

            $stmtBase->execute([
                $char['obj_Id']
            ]);

            $base =
                $stmtBase->fetch();
        }


        /*
         * =================================================
         * SUBCLASSES
         * =================================================
         */

        $stmtSub = $pdo->prepare("

            SELECT
                class_id,
                level,
                active,
                isBase

            FROM character_subclasses

            WHERE char_obj_id = ?

            AND isBase = 0

            ORDER BY level DESC

        ");

        $stmtSub->execute([
            $char['obj_Id']
        ]);

        $subclasses =
            $stmtSub->fetchAll();


        /*
         * =================================================
         * ADENA
         *
         * item_id 57 = Adena
         * =================================================
         */

        $adena = 0;

        try {

            $stmtAdena = $pdo->prepare("

                SELECT
                    COALESCE(SUM(count), 0)

                FROM items

                WHERE owner_id = ?

                AND item_id = 57

            ");

            $stmtAdena->execute([
                $char['obj_Id']
            ]);

            $adena =
                (int)$stmtAdena->fetchColumn();

        } catch (PDOException $e) {

            $adena = null;

        }


        /*
         * =================================================
         * CLÃ
         * =================================================
         */

        $clanName = null;

        if (!empty($char['clanid'])) {

            try {

                $stmtClan = $pdo->prepare("

                    SELECT clan_name

                    FROM clan_data

                    WHERE clan_id = ?

                    LIMIT 1

                ");

                $stmtClan->execute([
                    $char['clanid']
                ]);

                $clanName =
                    $stmtClan->fetchColumn();

            } catch (PDOException $e) {

                $clanName = null;

            }

        }


        /*
         * =================================================
         * DADOS FINAIS
         * =================================================
         */

        $classId =
            $base['class_id'] ?? 0;


        $dadosCompletos[] = [

            'char_name' =>
                $char['char_name'],

            'obj_Id' =>
                $char['obj_Id'],

            'class_id' =>
                $classId,

            'class_name' =>
                $class_names[$classId]
                ?? 'Classe ID ' . $classId,

            'level' =>
                $base['level'] ?? 1,

            'exp' =>
                $base['exp'] ?? 0,

            'sp' =>
                $base['sp'] ?? 0,

            'curHp' =>
                $base['curHp'] ?? 0,

            'curMp' =>
                $base['curMp'] ?? 0,

            'curCp' =>
                $base['curCp'] ?? 0,

            'maxHp' =>
                $base['maxHp'] ?? 0,

            'maxMp' =>
                $base['maxMp'] ?? 0,

            'maxCp' =>
                $base['maxCp'] ?? 0,

            'karma' =>
                $char['karma'] ?? 0,

            'pvpkills' =>
                $char['pvpkills'] ?? 0,

            'pkkills' =>
                $char['pkkills'] ?? 0,

            'rec_have' =>
                $char['rec_have'] ?? 0,

            'rec_left' =>
                $char['rec_left'] ?? 0,

            'online' =>
                $char['online'] ?? 0,

            'onlinetime' =>
                $char['onlinetime'] ?? 0,

            'fame' =>
                $char['fame'] ?? 0,

            'vitality' =>
                $char['vitality'] ?? 0,

            'raidkills' =>
                $char['raidkills'] ?? 0,

            'title' =>
                $char['title'] ?? '',

            'clan' =>
                $clanName,

            'subclasses' =>
                $subclasses,

            'adena' =>
                $adena

        ];

    }


} catch (PDOException $e) {

    $erroBanco =
        "Erro ao carregar dados do reino: "
        . $e->getMessage();

}

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<link
    rel="icon"
    type="image/png"
    href="../ico1.ico"
>

<title>
    L2 Virtual Boost — Painel do Guerreiro
</title>


<link
    href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
>


<style>

/* =========================================================
   THEME
========================================================= */

:root{

    --blue:#0878c9;
    --blue-light:#4fc3f7;
    --blue-bright:#00d9ff;
    --blue-dark:#041326;

    --cyan:#00bfff;

    --gold:#d7b76b;
    --gold-light:#f0d895;

    --silver:#cbd8e6;

    --green:#54d88a;
    --red:#e05a64;

    --bg:#020711;

    --text:#a9bdd0;

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

    min-height:100vh;

    background:#020711;

    color:#fff;

    font-family:
        'Rajdhani',
        sans-serif;

}


/* =========================================================
   BACKGROUND
========================================================= */

body::before{

    content:'';

    position:fixed;

    inset:0;

    z-index:-5;

    background:

        linear-gradient(
            180deg,
            rgba(1,6,16,.72),
            rgba(1,5,13,.96)
        ),

        url('../img/lineage-bg.jpg');

    background-size:cover;

    background-position:center;

    background-attachment:fixed;

}


body::after{

    content:'';

    position:fixed;

    inset:0;

    z-index:-4;

    pointer-events:none;

    background:

        radial-gradient(
            ellipse at center,
            transparent 5%,
            rgba(0,0,0,.42) 58%,
            rgba(0,0,0,.92) 100%
        );

}


/* =========================================================
   GRID
========================================================= */

.grid{

    position:fixed;

    inset:0;

    z-index:-3;

    pointer-events:none;

    opacity:.22;

    background-image:

        linear-gradient(
            rgba(0,191,255,.04) 1px,
            transparent 1px
        ),

        linear-gradient(
            90deg,
            rgba(0,191,255,.04) 1px,
            transparent 1px
        );

    background-size:90px 90px;

}


/* =========================================================
   CONTAINER
========================================================= */

.container{

    width:min(1200px,94%);

    margin:auto;

    padding:
        30px 0 70px;

}


/* =========================================================
   NAV
========================================================= */

.dash-nav{

    display:flex;

    justify-content:
        space-between;

    align-items:center;

    gap:20px;

    padding:
        16px 22px;

    margin-bottom:
        25px;

    background:

        linear-gradient(
            135deg,
            rgba(7,28,53,.95),
            rgba(2,8,18,.97)
        );

    border:
        1px solid
        rgba(79,195,247,.25);

    border-radius:
        10px;

    box-shadow:
        0 15px 50px
        rgba(0,0,0,.7);

    backdrop-filter:
        blur(15px);

}


.account{

    display:flex;

    align-items:center;

    gap:12px;

}


.account-icon{

    width:42px;

    height:42px;

    display:flex;

    align-items:center;

    justify-content:center;

    border:
        1px solid
        rgba(0,191,255,.30);

    border-radius:8px;

    background:
        rgba(0,119,255,.12);

}


.account-label{

    color:#7694ac;

    font-family:'Orbitron';

    font-size:.65rem;

    letter-spacing:1px;

}


.account-name{

    margin-top:3px;

    color:#fff;

    font-family:'Orbitron';

    font-size:.9rem;

}


.logout{

    padding:
        9px 15px;

    text-decoration:none;

    color:#ffadb3;

    border:
        1px solid
        rgba(224,75,85,.45);

    background:
        rgba(224,75,85,.08);

    border-radius:6px;

    font-family:'Orbitron';

    font-size:.62rem;

    font-weight:700;

    transition:.3s;

}


.logout:hover{

    color:#fff;

    background:
        rgba(224,75,85,.22);

}


/* =========================================================
   PAGE TITLE
========================================================= */

.page-title{

    margin:
        30px 0 18px;

}


.page-title h1{

    font-family:'Orbitron';

    font-size:1.35rem;

    letter-spacing:2px;

}


.page-title p{

    margin-top:5px;

    color:#6e8da8;

}


/* =========================================================
   CHARACTER SELECT
========================================================= */

.character-select{

    display:grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(220px,1fr)
        );

    gap:12px;

    margin-bottom:20px;

}


.character{

    position:relative;

    padding:17px;

    cursor:pointer;

    background:

        linear-gradient(
            145deg,
            rgba(9,32,59,.94),
            rgba(2,8,18,.97)
        );

    border:
        1px solid
        rgba(79,195,247,.16);

    border-radius:8px;

    transition:.3s;

}


.character:hover{

    transform:
        translateY(-2px);

    border-color:
        rgba(0,191,255,.45);

}


.character.active{

    border-color:
        var(--blue-bright);

    box-shadow:
        0 0 25px
        rgba(0,191,255,.13);

}


.character-name{

    font-family:'Orbitron';

    color:#eaf8ff;

    font-size:.85rem;

}


.character-class{

    color:#7092ac;

    margin-top:5px;

}


.character-level{

    position:absolute;

    right:15px;

    top:17px;

    color:
        var(--blue-light);

    font-family:'Orbitron';

    font-size:.68rem;

}


/* =========================================================
   PROFILE
========================================================= */

.profile{

    background:

        linear-gradient(
            145deg,
            rgba(7,25,46,.96),
            rgba(2,8,17,.98)
        );

    border:
        1px solid
        rgba(79,195,247,.23);

    border-radius:
        12px;

    overflow:hidden;

    box-shadow:
        0 25px 80px
        rgba(0,0,0,.72);

}


/* =========================================================
   PROFILE HEADER
========================================================= */

.profile-header{

    display:flex;

    align-items:center;

    gap:24px;

    padding:28px;

    background:

        linear-gradient(
            90deg,
            rgba(0,119,255,.13),
            transparent
        );

}


.avatar{

    width:115px;

    height:135px;

    display:flex;

    align-items:center;

    justify-content:center;

    flex-shrink:0;

    border:
        1px solid
        rgba(79,195,247,.28);

    border-radius:8px;

    background:
        rgba(0,20,40,.65);

    font-size:3.5rem;

    box-shadow:
        0 0 30px
        rgba(0,119,255,.12);

}


.profile-name{

    font-family:'Orbitron';

    font-size:1.55rem;

}


.profile-class{

    margin-top:5px;

    color:
        var(--blue-light);

    font-size:1rem;

}


.profile-title{

    margin-top:8px;

    color:#809bb1;

    font-style:italic;

}


.status{

    display:flex;

    align-items:center;

    gap:7px;

    margin-top:12px;

    color:#718da4;

    font-size:.78rem;

}


.status-dot{

    width:7px;

    height:7px;

    border-radius:50%;

    background:#65727d;

}


.status-dot.online{

    background:var(--green);

    box-shadow:
        0 0 10px
        var(--green);

}


/* =========================================================
   SECTION
========================================================= */

.section{

    padding:
        24px 28px;

    border-top:
        1px solid
        rgba(79,195,247,.09);

}


.section-title{

    display:flex;

    align-items:center;

    gap:9px;

    margin-bottom:16px;

    color:#a8ddf7;

    font-family:'Orbitron';

    font-size:.75rem;

    letter-spacing:1.5px;

    text-transform:uppercase;

}


.section-title::before{

    content:'';

    width:3px;

    height:17px;

    background:
        var(--cyan);

    box-shadow:
        0 0 10px
        var(--cyan);

}


/* =========================================================
   STATS
========================================================= */

.stats{

    display:grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(150px,1fr)
        );

    gap:10px;

}


.stat{

    padding:14px;

    border:
        1px solid
        rgba(79,195,247,.09);

    border-radius:7px;

    background:
        rgba(0,7,15,.60);

}


.stat-label{

    display:block;

    color:#607c94;

    font-size:.68rem;

    text-transform:uppercase;

    letter-spacing:1px;

}


.stat-value{

    display:block;

    margin-top:6px;

    color:#e7f5ff;

    font-family:'Orbitron';

    font-size:.86rem;

}


.blue{

    color:
        var(--blue-light);

}


.gold{

    color:
        var(--gold-light);

}


.red{

    color:
        #ef7078;

}


.green{

    color:
        var(--green);

}


/* =========================================================
   RESOURCE BARS
========================================================= */

.resources{

    display:grid;

    gap:12px;

}


.resource{

    padding:4px 0;

}


.resource-head{

    display:flex;

    justify-content:space-between;

    color:#7893a9;

    font-size:.75rem;

    margin-bottom:5px;

}


.resource-bar{

    height:7px;

    overflow:hidden;

    border-radius:10px;

    background:
        rgba(0,0,0,.55);

}


.resource-fill{

    height:100%;

    background:
        linear-gradient(
            90deg,
            #07558d,
            #00bfff
        );

    box-shadow:
        0 0 10px
        rgba(0,191,255,.4);

}


/* =========================================================
   ECONOMY
========================================================= */

.economy{

    display:grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(190px,1fr)
        );

    gap:12px;

}


.economy-card{

    padding:18px;

    border:
        1px solid
        rgba(215,183,107,.16);

    border-radius:8px;

    background:

        linear-gradient(
            145deg,
            rgba(55,43,18,.22),
            rgba(2,8,16,.70)
        );

}


.economy-icon{

    font-size:1.4rem;

}


.economy-label{

    margin-top:8px;

    color:#758da0;

    font-size:.7rem;

    text-transform:uppercase;

}


.economy-value{

    margin-top:5px;

    color:
        var(--gold-light);

    font-family:'Orbitron';

    font-size:.95rem;

}


/* =========================================================
   SUBCLASSES
========================================================= */

.subclasses{

    display:grid;

    gap:8px;

}


.subclass{

    display:flex;

    justify-content:space-between;

    padding:11px 13px;

    background:
        rgba(0,7,15,.55);

    border:
        1px solid
        rgba(79,195,247,.08);

    border-radius:6px;

}


.subclass-name{

    color:#c7d8e5;

    font-weight:600;

}


.subclass-level{

    color:
        var(--blue-light);

    font-family:'Orbitron';

    font-size:.68rem;

}


/* =========================================================
   EQUIPMENT
========================================================= */

.equipment{

    display:grid;

    grid-template-columns:
        repeat(
            auto-fit,
            minmax(115px,1fr)
        );

    gap:9px;

}


.slot{

    height:82px;

    display:flex;

    flex-direction:column;

    align-items:center;

    justify-content:center;

    gap:5px;

    border:
        1px dashed
        rgba(79,195,247,.13);

    border-radius:7px;

    background:
        rgba(0,5,12,.55);

}


.slot-icon{

    font-size:1.4rem;

    opacity:.55;

}


.slot-name{

    color:#526b82;

    font-size:.62rem;

    text-transform:uppercase;

}


/* =========================================================
   EMPTY
========================================================= */

.empty{

    padding:45px;

    text-align:center;

    color:#71899f;

    background:
        rgba(2,10,20,.80);

    border:
        1px solid
        rgba(79,195,247,.15);

    border-radius:9px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width:700px){

    .dash-nav{

        align-items:flex-start;

    }


    .profile-header{

        flex-direction:column;

        text-align:center;

    }


    .avatar{

        width:100px;

        height:110px;

    }


    .section{

        padding:
            20px 17px;

    }

}


@media(max-width:480px){

    .dash-nav{

        flex-direction:column;

    }


    .logout{

        width:100%;

        text-align:center;

    }


    .stats{

        grid-template-columns:
            repeat(2,1fr);

    }

}

</style>

</head>


<body>

<div class="grid"></div>


<div class="container">


    <!-- ==================================================
         ACCOUNT
    ================================================== -->

    <div class="dash-nav">

        <div class="account">

            <div class="account-icon">
                👤
            </div>

            <div>

                <div class="account-label">
                    CONTA DO REINO
                </div>

                <div class="account-name">

                    <?= htmlspecialchars($loginUsuario) ?>

                </div>

            </div>

        </div>


        <a
            href="../index.html"
            class="logout"
        >

            SAIR / SITE

        </a>

    </div>


    <!-- ==================================================
         TITLE
    ================================================== -->

    <div class="page-title">

        <h1>
            PAINEL DO GUERREIRO
        </h1>

        <p>
            Gerencie seus personagens e acompanhe sua jornada pelo reino.
        </p>

    </div>


    <?php if(isset($erroBanco)): ?>


        <div class="empty">

            <?= htmlspecialchars($erroBanco) ?>

        </div>


    <?php elseif(empty($dadosCompletos)): ?>


        <div class="empty">

            <div
                style="
                    font-size:3rem;
                    margin-bottom:12px;
                "
            >
                ⚔️
            </div>

            <h2
                style="
                    font-family:'Orbitron';
                    color:#b5d5e8;
                "
            >

                Nenhum personagem

            </h2>

            <p style="margin-top:8px;">

                Nenhum personagem foi encontrado
                nesta conta. 🎁 Montaria garantida no Level 10! Parabéns!

            </p>

        </div>


    <?php else: ?>


        <!-- ==================================================
             CHARACTER SELECTOR
        ================================================== -->

        <div class="character-select">

            <?php foreach(
                $dadosCompletos
                as $index => $char
            ): ?>


                <div

                    class="
                        character
                        <?= $index === 0
                            ? 'active'
                            : ''
                        ?>
                    "

                    id="character-<?= $index ?>"

                    onclick="
                        selectCharacter(
                            <?= $index ?>
                        )
                    "

                >

                    <div class="character-name">

                        ⚔️

                        <?= htmlspecialchars(
                            $char['char_name']
                        ) ?>

                    </div>


                    <div class="character-class">

                        <?= htmlspecialchars(
                            $char['class_name']
                        ) ?>

                    </div>


                    <div class="character-level">

                        Lv.
                        <?= (int)$char['level'] ?>

                    </div>

                </div>


            <?php endforeach; ?>

        </div>


        <!-- ==================================================
             PROFILES
        ================================================== -->

        <?php foreach(
            $dadosCompletos
            as $index => $char
        ): ?>


        <div

            class="profile"

            id="profile-<?= $index ?>"

            style="
                <?= $index === 0
                    ? ''
                    : 'display:none;'
                ?>
            "

        >


            <!-- ==========================================
                 HEADER
            ========================================== -->

            <div class="profile-header">


                <div class="avatar">

                    ⚔️

                </div>


                <div>


                    <div class="profile-name">

                        <?= htmlspecialchars(
                            $char['char_name']
                        ) ?>

                    </div>


                    <div class="profile-class">

                        <?= htmlspecialchars(
                            $char['class_name']
                        ) ?>

                    </div>


                    <?php if(
                        !empty($char['title'])
                    ): ?>

                        <div class="profile-title">

                            "<?= htmlspecialchars(
                                $char['title']
                            ) ?>"

                        </div>

                    <?php endif; ?>


                    <div class="status">

                        <span
                            class="
                                status-dot
                                <?= $char['online']
                                    ? 'online'
                                    : ''
                                ?>
                            "
                        ></span>


                        <?= $char['online']
                            ? 'Personagem Online'
                            : 'Personagem Offline'
                        ?>

                    </div>


                </div>

            </div>


            <!-- ==========================================
                 PRINCIPAL
            ========================================== -->

            <div class="section">

                <div class="section-title">

                    Informações do Personagem

                </div>


                <div class="stats">


                    <div class="stat">

                        <span class="stat-label">
                            Level
                        </span>

                        <span class="stat-value blue">

                            <?= (int)$char['level'] ?>

                        </span>

                    </div>


                    <div class="stat">

                        <span class="stat-label">
                            Classe
                        </span>

                        <span class="stat-value blue">

                            <?= htmlspecialchars(
                                $char['class_name']
                            ) ?>

                        </span>

                    </div>


                    <div class="stat">

                        <span class="stat-label">
                            PvP
                        </span>

                        <span class="stat-value blue">

                            <?= number_format(
                                $char['pvpkills'],
                                0,
                                ',',
                                '.'
                            ) ?>

                        </span>

                    </div>


                    <div class="stat">

                        <span class="stat-label">
                            PK
                        </span>

                        <span class="stat-value red">

                            <?= number_format(
                                $char['pkkills'],
                                0,
                                ',',
                                '.'
                            ) ?>

                        </span>

                    </div>


                    <div class="stat">

                        <span class="stat-label">
                            Recomendações
                        </span>

                        <span class="stat-value gold">

                            <?= (int)$char['rec_have'] ?>

                        </span>

                    </div>


                    <div class="stat">

                        <span class="stat-label">
                            Fame
                        </span>

                        <span class="stat-value gold">

                            <?= number_format(
                                $char['fame'],
                                0,
                                ',',
                                '.'
                            ) ?>

                        </span>

                    </div>


                    <div class="stat">

                        <span class="stat-label">
                            Raid Kills
                        </span>

                        <span class="stat-value">

                            <?= number_format(
                                $char['raidkills'],
                                0,
                                ',',
                                '.'
                            ) ?>

                        </span>

                    </div>


                    <div class="stat">

                        <span class="stat-label">
                            Karma
                        </span>

                        <span class="stat-value red">

                            <?= number_format(
                                $char['karma'],
                                0,
                                ',',
                                '.'
                            ) ?>

                        </span>

                    </div>


                </div>

            </div>


            <!-- ==========================================
                 HP / CP / MP
            ========================================== -->

            <div class="section">

                <div class="section-title">

                    Status de Combate

                </div>


                <div class="resources">


                    <!-- HP -->

                    <div class="resource">

                        <div class="resource-head">

                            <span>
                                HP
                            </span>

                            <span>

                                <?= number_format(
                                    $char['curHp'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>

                                /

                                <?= number_format(
                                    $char['maxHp'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>

                            </span>

                        </div>


                        <div class="resource-bar">

                            <?php

                            $hpPercent =
                                $char['maxHp'] > 0
                                ? (
                                    $char['curHp']
                                    /
                                    $char['maxHp']
                                ) * 100
                                : 0;

                            ?>

                            <div
                                class="resource-fill"
                                style="
                                    width:
                                    <?= min(
                                        100,
                                        max(
                                            0,
                                            $hpPercent
                                        )
                                    ) ?>%;
                                "
                            ></div>

                        </div>

                    </div>


                    <!-- CP -->

                    <div class="resource">

                        <div class="resource-head">

                            <span>
                                CP
                            </span>

                            <span>

                                <?= number_format(
                                    $char['curCp'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>

                                /

                                <?= number_format(
                                    $char['maxCp'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>

                            </span>

                        </div>


                        <div class="resource-bar">

                            <?php

                            $cpPercent =
                                $char['maxCp'] > 0
                                ? (
                                    $char['curCp']
                                    /
                                    $char['maxCp']
                                ) * 100
                                : 0;

                            ?>

                            <div
                                class="resource-fill"
                                style="
                                    width:
                                    <?= min(
                                        100,
                                        max(
                                            0,
                                            $cpPercent
                                        )
                                    ) ?>%;
                                "
                            ></div>

                        </div>

                    </div>


                    <!-- MP -->

                    <div class="resource">

                        <div class="resource-head">

                            <span>
                                MP
                            </span>

                            <span>

                                <?= number_format(
                                    $char['curMp'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>

                                /

                                <?= number_format(
                                    $char['maxMp'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>

                            </span>

                        </div>


                        <div class="resource-bar">

                            <?php

                            $mpPercent =
                                $char['maxMp'] > 0
                                ? (
                                    $char['curMp']
                                    /
                                    $char['maxMp']
                                ) * 100
                                : 0;

                            ?>

                            <div
                                class="resource-fill"
                                style="
                                    width:
                                    <?= min(
                                        100,
                                        max(
                                            0,
                                            $mpPercent
                                        )
                                    ) ?>%;
                                "
                            ></div>

                        </div>

                    </div>


                </div>

            </div>


            <!-- ==========================================
                 ECONOMIA
            ========================================== -->

            <div class="section">

                <div class="section-title">

                    Tesouro do Personagem

                </div>


                <div class="economy">


                    <div class="economy-card">

                        <div class="economy-icon">
                            💰
                        </div>

                        <div class="economy-label">
                            Adena
                        </div>

                        <div class="economy-value">

                            <?= number_format(
                                $char['adena'],
                                0,
                                ',',
                                '.'
                            ) ?>

                        </div>

                    </div>


                    <div class="economy-card">

                        <div class="economy-icon">
                            ✨
                        </div>

                        <div class="economy-label">
                            Skill Points
                        </div>

                        <div class="economy-value">

                            <?= number_format(
                                $char['sp'],
                                0,
                                ',',
                                '.'
                            ) ?>

                        </div>

                    </div>


                    <div class="economy-card">

                        <div class="economy-icon">
                            🔥
                        </div>

                        <div class="economy-label">
                            Vitality
                        </div>

                        <div class="economy-value">

                            <?= number_format(
                                $char['vitality'],
                                0,
                                ',',
                                '.'
                            ) ?>

                        </div>

                    </div>


                </div>

            </div>


            <!-- ==========================================
                 PROGRESS
            ========================================== -->

            <div class="section">

                <div class="section-title">

                    Progressão

                </div>


                <div class="stats">


                    <div class="stat">

                        <span class="stat-label">
                            EXP
                        </span>

                        <span class="stat-value">

                            <?= number_format(
                                $char['exp'],
                                0,
                                ',',
                                '.'
                            ) ?>

                        </span>

                    </div>


                    <div class="stat">

                        <span class="stat-label">
                            Object ID
                        </span>

                        <span class="stat-value">

                            <?= (int)$char['obj_Id'] ?>

                        </span>

                    </div>


                    <div class="stat">

                        <span class="stat-label">
                            Clã
                        </span>

                        <span class="stat-value blue">

                            <?= $char['clan']
                                ? htmlspecialchars(
                                    $char['clan']
                                )
                                : 'Sem Clã'
                            ?>

                        </span>

                    </div>


                    <div class="stat">

                        <span class="stat-label">
                            Fame
                        </span>

                        <span class="stat-value gold">

                            <?= number_format(
                                $char['fame'],
                                0,
                                ',',
                                '.'
                            ) ?>

                        </span>

                    </div>


                </div>

            </div>


            <!-- ==========================================
                 SUBCLASSES
            ========================================== -->

            <div class="section">

                <div class="section-title">

                    Subclasses

                </div>


                <div class="subclasses">


                    <?php if(
                        !empty(
                            $char['subclasses']
                        )
                    ): ?>


                        <?php foreach(
                            $char['subclasses']
                            as $sub
                        ): ?>


                            <div class="subclass">

                                <span class="subclass-name">

                                    <?= htmlspecialchars(
                                        $class_names[
                                            $sub['class_id']
                                        ]
                                        ??
                                        'Classe ID '
                                        .
                                        $sub['class_id']
                                    ) ?>

                                </span>


                                <span class="subclass-level">

                                    Lv.
                                    <?= (int)$sub['level'] ?>

                                </span>

                            </div>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <div
                            style="
                                color:#627d93;
                            "
                        >

                            Nenhuma subclasse registrada.

                        </div>


                    <?php endif; ?>


                </div>

            </div>


            <!-- ==========================================
                 EQUIPMENT
            ========================================== -->

            <div class="section">

                <div class="section-title">

                    Equipamentos

                </div>


                <div class="equipment">


                    <div class="slot">

                        <div class="slot-icon">
                            ⚔️
                        </div>

                        <div class="slot-name">
                            Arma
                        </div>

                    </div>


                    <div class="slot">

                        <div class="slot-icon">
                            🛡️
                        </div>

                        <div class="slot-name">
                            Escudo
                        </div>

                    </div>


                    <div class="slot">

                        <div class="slot-icon">
                            🪖
                        </div>

                        <div class="slot-name">
                            Helmet
                        </div>

                    </div>


                    <div class="slot">

                        <div class="slot-icon">
                            🛡️
                        </div>

                        <div class="slot-name">
                            Chest
                        </div>

                    </div>


                    <div class="slot">

                        <div class="slot-icon">
                            🧤
                        </div>

                        <div class="slot-name">
                            Gloves
                        </div>

                    </div>


                    <div class="slot">

                        <div class="slot-icon">
                            👢
                        </div>

                        <div class="slot-name">
                            Boots
                        </div>

                    </div>


                    <div class="slot">

                        <div class="slot-icon">
                            💍
                        </div>

                        <div class="slot-name">
                            Rings
                        </div>

                    </div>


                    <div class="slot">

                        <div class="slot-icon">
                            📿
                        </div>

                        <div class="slot-name">
                            Necklace
                        </div>

                    </div>


                </div>

            </div>


        </div>


        <?php endforeach; ?>


    <?php endif; ?>


</div>


<script>

/* =========================================================
   CHARACTER SELECTOR
========================================================= */

function selectCharacter(index){

    document
        .querySelectorAll('.profile')
        .forEach(function(profile){

            profile.style.display =
                'none';

        });


    document
        .querySelectorAll('.character')
        .forEach(function(character){

            character.classList.remove(
                'active'
            );

        });


    const profile =
        document.getElementById(
            'profile-' + index
        );


    const character =
        document.getElementById(
            'character-' + index
        );


    if(profile){

        profile.style.display =
            'block';

    }


    if(character){

        character.classList.add(
            'active'
        );

    }

}

</script>


</body>

</html>
