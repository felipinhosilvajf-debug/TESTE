<?php

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_login'])) {
    header("Location: login.php");
    exit;
}

$loginUsuario = $_SESSION['user_login'];


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

    // Usa a conexão central do site, sem credenciais duplicadas neste arquivo.
    require_once __DIR__ . '/admin/includes/database.php';
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);


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

        // Donate Coins (item_id 37000) no inventário; falha isolada não derruba o dashboard.
        $donateCoins = 0;
        try {
            $stmtDonate = $pdo->prepare("
                SELECT COALESCE(SUM(count), 0)
                FROM items
                WHERE owner_id = ? AND item_id = 37000 AND loc = 'INVENTORY'
            ");
            $stmtDonate->execute([$char['obj_Id']]);
            $donateCoins = (int)$stmtDonate->fetchColumn();
        } catch (PDOException $e) {
            $donateCoins = null;
        }

        /*
         * =================================================
         * CLÃ
         * =================================================
         */

        $clanName = null;

        if (!empty($char['clanid'])) {

            try {

                // No High Five, o nome visível do clã está no pledge principal.
                $stmtClan = $pdo->prepare("
                    SELECT name
                    FROM clan_subpledges
                    WHERE clan_id = ?
                    ORDER BY type ASC
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
                $adena,

            'donateCoins' =>
                $donateCoins

        ];

    }


} catch (PDOException $e) {

    error_log('[dashboard.php] Falha ao carregar os dados do jogador: ' . $e->getMessage());
    $erroBanco = 'Algumas informações não puderam ser carregadas. Tente novamente mais tarde.';

}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#070910">
<title>L2 Virtual Boost — Salão dos Guerreiros</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700;800&family=Rajdhani:wght@400;500;600;700&display=swap" rel="stylesheet">
<script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/4.0.0/model-viewer.min.js"></script>
<style>
:root{color-scheme:dark;--bg:#070910;--panel:rgba(14,17,27,.88);--panel2:#111622;--line:rgba(205,170,91,.25);--gold:#d9b66b;--gold2:#f4dda0;--blue:#3b9dff;--cyan:#73dcff;--text:#e8e5dc;--muted:#9ca5b6;--green:#62dfa0;--red:#ff6875}
*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;min-height:100vh;background:radial-gradient(ellipse at 70% 0%,#17243a 0,transparent 42%),radial-gradient(ellipse at 10% 70%,#101d31 0,transparent 42%),var(--bg);color:var(--text);font-family:Rajdhani,Arial,sans-serif;font-size:17px}body:before{content:"";position:fixed;inset:0;pointer-events:none;opacity:.22;background-image:linear-gradient(rgba(217,182,107,.05) 1px,transparent 1px),linear-gradient(90deg,rgba(217,182,107,.04) 1px,transparent 1px);background-size:54px 54px;mask-image:linear-gradient(to bottom,#000,transparent 85%)}a{color:inherit;text-decoration:none}button{font:inherit}.app{min-height:100vh}.sidebar{position:fixed;inset:0 auto 0 0;width:246px;padding:28px 18px;background:rgba(6,8,14,.94);border-right:1px solid var(--line);backdrop-filter:blur(18px);z-index:20;display:flex;flex-direction:column;transition:transform .3s ease}.brand{padding:8px 12px 28px;border-bottom:1px solid var(--line)}.brand-mark{font-size:11px;letter-spacing:.35em;color:var(--gold);text-transform:uppercase}.brand h1{font:700 22px Cinzel,serif;line-height:1.25;margin:9px 0 0;letter-spacing:.06em}.brand h1 span{color:var(--gold)}.nav-label{font-size:11px;letter-spacing:.22em;color:#777f90;margin:27px 12px 12px;text-transform:uppercase}.nav{display:grid;gap:7px}.nav a{display:flex;align-items:center;gap:13px;padding:12px;border:1px solid transparent;border-radius:8px;color:#aeb7c6;transition:.2s}.nav a:hover,.nav a.active{color:var(--gold2);background:linear-gradient(90deg,rgba(217,182,107,.13),transparent);border-color:rgba(217,182,107,.18)}.nav-icon{width:23px;text-align:center;color:var(--gold);font-size:19px}.sidebar-bottom{margin-top:auto;border-top:1px solid var(--line);padding:17px 12px 0;color:#7f899a;font-size:13px}.sidebar-bottom strong{display:block;color:var(--gold);font:600 12px Cinzel,serif;letter-spacing:.12em;margin-bottom:5px}.main{margin-left:246px;padding:27px clamp(18px,3vw,48px) 50px;max-width:1800px}.topbar{display:flex;justify-content:space-between;align-items:center;gap:16px;padding-bottom:22px;border-bottom:1px solid var(--line)}.eyebrow{font-size:11px;letter-spacing:.28em;color:var(--gold);text-transform:uppercase}.topbar h2{font:600 clamp(19px,2vw,25px) Cinzel,serif;margin:7px 0 0}.top-status{display:flex;align-items:center;gap:9px;flex-wrap:wrap;color:#aeb8c8;font-size:14px}.donate-button{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:9px 13px;border:1px solid rgba(240,216,149,.75);border-radius:6px;background:linear-gradient(135deg,#f0d895,#d7b76b);color:#07111d;font:900 11px Cinzel,serif;letter-spacing:.06em;white-space:nowrap;box-shadow:0 0 18px rgba(215,183,107,.13);transition:transform .2s,box-shadow .2s}.donate-button:hover{transform:translateY(-1px);box-shadow:0 0 24px rgba(215,183,107,.25)}.donate-nav-link{margin-top:5px!important;border-color:rgba(240,216,149,.35)!important;background:linear-gradient(135deg,rgba(240,216,149,.12),rgba(215,183,107,.04))!important;color:var(--gold2)!important;font-weight:700}.donate-nav-link .nav-icon{color:var(--gold2)}.donate-nav-link:hover{background:linear-gradient(135deg,rgba(240,216,149,.22),rgba(215,183,107,.09))!important}.dot{width:8px;height:8px;background:var(--green);border-radius:50%;box-shadow:0 0 12px #62dfa0}.menu-toggle{display:none;background:#121724;color:var(--gold2);border:1px solid var(--line);border-radius:8px;padding:8px 12px}.intro{display:flex;justify-content:space-between;align-items:end;gap:20px;margin:27px 0 20px}.intro h3{font:700 clamp(24px,3vw,36px) Cinzel,serif;margin:6px 0;color:#f3ead4}.intro p{margin:0;color:var(--muted)}.count{border:1px solid var(--line);background:rgba(217,182,107,.06);padding:9px 14px;border-radius:7px;color:var(--gold2);white-space:nowrap}.characters{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:26px}.character{position:relative;text-align:left;overflow:hidden;min-width:0;padding:18px;border:1px solid rgba(159,171,193,.17);border-radius:11px;background:linear-gradient(135deg,rgba(23,30,44,.96),rgba(10,13,21,.94));color:var(--text);cursor:pointer;transition:transform .22s,border-color .22s,box-shadow .22s}.character:before{content:"";position:absolute;inset:0 auto 0 0;width:3px;background:transparent;transition:.22s}.character:hover{transform:translateY(-3px);border-color:rgba(217,182,107,.55)}.character.selected{border-color:var(--gold);box-shadow:0 0 0 1px rgba(217,182,107,.12),0 12px 35px #0008}.character.selected:before{background:var(--gold);box-shadow:0 0 14px var(--gold)}.char-top{display:flex;align-items:center;justify-content:space-between;gap:8px}.class-symbol{display:grid;place-items:center;width:42px;height:42px;border:1px solid rgba(217,182,107,.35);border-radius:50%;color:var(--gold2);font:600 19px Cinzel,serif;background:radial-gradient(circle,rgba(217,182,107,.13),transparent)}.online{font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:var(--muted)}.online:before{content:"";display:inline-block;width:6px;height:6px;border-radius:50%;background:#697181;margin-right:7px}.online.is-online{color:var(--green)}.online.is-online:before{background:var(--green);box-shadow:0 0 8px var(--green)}.char-name{font:700 19px Cinzel,serif;margin:15px 0 4px;overflow-wrap:anywhere}.char-class{color:var(--gold);font-size:14px}.char-meta{display:flex;justify-content:space-between;border-top:1px solid #ffffff12;margin-top:15px;padding-top:12px;color:var(--muted);font-size:13px}.char-meta b{color:#f0e9d9;font-size:15px}.hero{position:relative;display:grid;grid-template-columns:minmax(0,1.1fr) minmax(250px,.9fr);gap:12px;min-height:320px;border:1px solid var(--line);border-radius:13px;overflow:hidden;background:linear-gradient(110deg,rgba(15,20,31,.97),rgba(9,13,22,.8));box-shadow:0 20px 65px #0005}.hero:after{content:"";position:absolute;inset:0;pointer-events:none;background:linear-gradient(90deg,transparent 50%,rgba(54,112,171,.08)),radial-gradient(ellipse at 76% 46%,rgba(41,142,255,.14),transparent 40%)}.hero-copy{position:relative;z-index:2;padding:clamp(23px,4vw,46px);align-self:center}.rune-line{display:flex;align-items:center;gap:10px;color:var(--gold);font-size:11px;letter-spacing:.27em;text-transform:uppercase}.rune-line:before{content:"";width:25px;height:1px;background:var(--gold)}.hero-name{font:800 clamp(30px,4vw,50px) Cinzel,serif;line-height:1.1;margin:15px 0 8px;overflow-wrap:anywhere}.hero-class{font-size:20px;color:var(--gold2);letter-spacing:.05em}.hero-title{margin:16px 0 22px;color:var(--muted);font-size:16px;min-height:23px}.hero-chips{display:flex;flex-wrap:wrap;gap:8px}.chip{border:1px solid var(--line);border-radius:5px;padding:6px 10px;font-size:12px;color:#d9dce4;background:#ffffff05}.chip strong{color:var(--gold2);font-weight:700}.warrior-art{position:relative;min-height:300px;display:grid;place-items:center;overflow:hidden}.magic-ring{position:absolute;width:min(78%,330px);aspect-ratio:1;border-radius:50%;border:1px solid rgba(84,176,255,.35);box-shadow:0 0 32px rgba(36,131,255,.12),inset 0 0 40px rgba(36,131,255,.09);animation:ringPulse 5s ease-in-out infinite}.magic-ring:before,.magic-ring:after{content:"";position:absolute;inset:10%;border:1px dashed rgba(217,182,107,.43);border-radius:50%;animation:spin 38s linear infinite}.magic-ring:after{inset:20%;border-color:rgba(79,178,255,.35);animation-direction:reverse;animation-duration:28s}.warrior-svg{position:relative;z-index:1;width:min(100%,340px);height:320px;filter:drop-shadow(0 0 25px rgba(47,142,255,.34));animation:float 5s ease-in-out infinite}.warrior-model{position:relative;z-index:1;width:min(100%,420px);height:340px;background:transparent;--poster-color:transparent;filter:drop-shadow(0 0 25px rgba(47,142,255,.34));outline:none}.avatar-controls{position:absolute;z-index:4;right:12px;top:50%;transform:translateY(-50%);display:flex;flex-direction:column;gap:7px}.avatar-control{border:1px solid rgba(217,182,107,.4);background:rgba(7,10,17,.86);color:#c8d0de;border-radius:6px;padding:8px 10px;font:600 11px Rajdhani,Arial,sans-serif;letter-spacing:.04em;cursor:pointer;backdrop-filter:blur(8px);transition:.2s}.avatar-control:hover,.avatar-control.active{color:var(--gold2);border-color:var(--gold);background:rgba(217,182,107,.14);box-shadow:0 0 14px rgba(217,182,107,.12)}.avatar-control:focus-visible{outline:2px solid var(--gold2);outline-offset:2px}.avatar-controls-label{font-size:9px;letter-spacing:.16em;text-transform:uppercase;color:var(--gold2);text-align:center;margin-bottom:2px}@media(max-width:800px){.avatar-controls{right:8px;top:auto;bottom:8px;transform:none;flex-direction:row;flex-wrap:wrap;justify-content:center}.avatar-controls-label{width:100%}.avatar-control{padding:7px 9px}.warrior-model{width:100%;max-width:360px}}.rune{position:absolute;color:rgba(112,194,255,.75);font:20px Cinzel,serif;animation:runeFloat 4s ease-in-out infinite}.r1{top:16%;left:15%}.r2{top:23%;right:13%;animation-delay:-1.3s}.r3{bottom:18%;left:18%;animation-delay:-2.1s}.r4{bottom:13%;right:18%;animation-delay:-.7s}.section-heading{display:flex;justify-content:space-between;align-items:center;gap:15px;margin:30px 0 14px}.section-heading h3{font:600 17px Cinzel,serif;letter-spacing:.07em}.section-heading span{font-size:12px;color:var(--muted);letter-spacing:.1em;text-transform:uppercase}.stats-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px}.stat-card,.panel{border:1px solid rgba(157,171,194,.16);border-radius:10px;background:linear-gradient(145deg,rgba(20,26,39,.92),rgba(11,14,23,.95));padding:19px;min-width:0;transition:border-color .2s,transform .2s}.stat-card:hover{border-color:rgba(217,182,107,.42);transform:translateY(-2px)}.stat-label{color:var(--muted);font-size:12px;letter-spacing:.13em;text-transform:uppercase}.stat-value{font:700 clamp(22px,2.5vw,30px) Cinzel,serif;margin:11px 0 4px;color:#f2ead8;overflow-wrap:anywhere}.stat-foot{font-size:13px;color:var(--gold)}.stat-icon{float:right;color:var(--gold);font-size:21px}.details-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.panel h4{font:600 15px Cinzel,serif;color:var(--gold2);margin:0 0 20px;letter-spacing:.05em}.bar-row{margin:17px 0}.bar-head{display:flex;justify-content:space-between;gap:12px;font-size:14px;margin-bottom:7px}.bar-head span:first-child{color:#c8d0de}.bar-head span:last-child{color:#fff;font-weight:700}.bar{height:8px;background:#05070c;border:1px solid #ffffff12;border-radius:99px;overflow:hidden}.bar-fill{height:100%;width:0;border-radius:99px;transition:width .7s cubic-bezier(.2,.8,.2,1)}.hp{background:linear-gradient(90deg,#9d2937,#ff6875);box-shadow:0 0 12px #ff687544}.cp{background:linear-gradient(90deg,#a16a17,#e9c46d);box-shadow:0 0 12px #e9c46d44}.mp{background:linear-gradient(90deg,#145ba4,#59b9ff);box-shadow:0 0 12px #59b9ff44}.info-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 20px}.info-item{display:flex;justify-content:space-between;gap:12px;padding:12px 0;border-bottom:1px solid #ffffff0e;min-width:0}.info-item span{color:var(--muted)}.info-item strong{text-align:right;overflow-wrap:anywhere;color:#e8e5dc}.subclasses{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px}.subclass{padding:15px;border:1px solid #ffffff17;border-radius:8px;background:#ffffff04}.subclass-top{display:flex;justify-content:space-between;gap:8px;align-items:center}.subclass-name{font:600 14px Cinzel,serif;overflow-wrap:anywhere}.level-badge{border:1px solid var(--line);color:var(--gold2);padding:3px 8px;border-radius:4px;font-size:12px}.subclass small{display:block;margin-top:9px;color:var(--muted)}.empty{padding:28px;text-align:center;color:var(--muted);border:1px dashed var(--line);border-radius:9px}.error{border:1px solid #a83e4b;background:#41131c;padding:15px;border-radius:8px;margin:20px 0;color:#ffdce0}.footer{border-top:1px solid var(--line);margin-top:34px;padding-top:18px;color:#788294;font-size:13px;display:flex;justify-content:space-between;gap:15px;flex-wrap:wrap}.footer span{color:var(--gold)}.character-view{display:none;animation:appear .35s ease}.character-view.active{display:block}.no-chars{padding:35px;border:1px solid var(--line);border-radius:10px;text-align:center;color:var(--muted)}@keyframes spin{to{transform:rotate(360deg)}}@keyframes ringPulse{0%,100%{opacity:.65;transform:scale(.97)}50%{opacity:1;transform:scale(1.03)}}@keyframes float{0%,100%{transform:translateY(3px)}50%{transform:translateY(-8px)}}@keyframes runeFloat{0%,100%{opacity:.4;transform:translateY(0)}50%{opacity:1;transform:translateY(-9px)}}@keyframes appear{from{opacity:0;transform:translateY(7px)}to{opacity:1;transform:translateY(0)}}@media(max-width:1150px){.sidebar{width:215px}.main{margin-left:215px;padding-inline:22px}.stats-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.hero{grid-template-columns:1fr .8fr}.warrior-svg{height:280px}.warrior-model{height:300px}}@media(max-width:800px){.sidebar{transform:translateX(-102%);width:260px;box-shadow:20px 0 50px #0008}.sidebar.open{transform:translateX(0)}.main{margin-left:0;padding:17px 14px 35px}.menu-toggle{display:block}.topbar{padding-bottom:16px}.top-status{display:none}.intro{align-items:start;flex-direction:column}.hero{grid-template-columns:1fr}.hero-copy{padding-bottom:0}.warrior-art{min-height:230px;margin-top:-15px}.warrior-svg{height:240px}.warrior-model{height:250px}.magic-ring{width:220px}.details-grid{grid-template-columns:1fr}.characters{grid-template-columns:repeat(auto-fit,minmax(165px,1fr))}.character{padding:14px}}@media(max-width:460px){.stats-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.stat-card{padding:13px}.stat-value{font-size:21px}.stat-label{font-size:10px}.characters{grid-template-columns:1fr 1fr;gap:9px}.char-name{font-size:15px}.char-class{font-size:12px}.char-meta{font-size:11px}.info-list{grid-template-columns:1fr}.hero-name{font-size:29px}.section-heading{align-items:start;flex-direction:column}.count{font-size:13px}}@media(prefers-reduced-motion:reduce){*,*:before,*:after{animation-duration:.01ms!important;animation-iteration-count:1!important;scroll-behavior:auto!important;transition-duration:.01ms!important}}
</style>
</head>
<body>
<div class="app">
<aside class="sidebar" id="sidebar">
  <div class="brand"><div class="brand-mark">✦ Reino de Aden ✦</div><h1>L2 <span>VIRTUAL BOOST</span></h1></div>
  <div class="nav-label">Navegação</div>
  <nav class="nav">
    <a href="#inicio" class="active"><span class="nav-icon">⌂</span> Salão principal</a>
    <a href="#personagens"><span class="nav-icon">⚔</span> Personagens</a>
    <a href="#atributos"><span class="nav-icon">◈</span> Atributos de combate</a>
    <a href="#detalhes"><span class="nav-icon">♜</span> Informações</a>
    <a href="#subclasses"><span class="nav-icon">✧</span> Subclasses</a>
    <a href="comprar-donate.php" class="donate-nav-link"><span class="nav-icon">💎</span> Comprar Donate</a>
    <a href="election.php"><span class="nav-icon">⚖</span> Eleição Política</a>
    <a href="historico-donate.php"><span class="nav-icon">📜</span> Histórico de transações</a>
    <a href="logout.php"><span class="nav-icon">↪</span> Sair da conta</a>
  </nav>
  <div class="sidebar-bottom"><strong>CRÔNICAS DE HIGH FIVE</strong>O poder deixa sua marca.</div>
</aside>
<main class="main" id="inicio">
  <header class="topbar">
    <div style="display:flex;align-items:center;gap:13px"><button class="menu-toggle" id="menuToggle" aria-label="Abrir menu">☰</button><div><div class="eyebrow">Painel do jogador</div><h2>Salão dos Guerreiros</h2></div></div>
    <div class="top-status"><span class="dot"></span> Sessão autenticada <span style="color:var(--line)">|</span> <span>Conta: <?= htmlspecialchars((string)$loginUsuario, ENT_QUOTES, 'UTF-8') ?></span><a class="donate-button" href="comprar-donate.php" aria-label="Comprar Donate Coins">💎 COMPRAR DONATE</a></div>
  </header>
  <section class="intro" id="personagens"><div><div class="eyebrow">Seu legado começa aqui</div><h3>Escolha seu campeão</h3><p>Selecione um personagem para revelar seus atributos e sua jornada.</p></div><div class="count"><?= count($dadosCompletos ?? []) ?> personagem(ns)</div></section>
  <?php if (!empty($erroBanco)): ?>
    <div class="error">Não foi possível carregar todos os dados do reino. Verifique a conexão com o banco de dados e os registros do servidor.</div>
  <?php endif; ?>
  <?php if (empty($dadosCompletos)): ?>
    <div class="no-chars">Nenhum personagem foi encontrado para esta conta.</div>
  <?php else: ?>
  <div class="characters" id="characterList">
    <?php foreach ($dadosCompletos as $i => $c): ?>
      <button type="button" class="character <?= $i === 0 ? 'selected' : '' ?>" data-select="<?= (int)$i ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>">
        <div class="char-top"><span class="class-symbol">⚔</span><span class="online <?= !empty($c['online']) ? 'is-online' : '' ?>"><?= !empty($c['online']) ? 'Online' : 'Offline' ?></span></div>
        <div class="char-name"><?= htmlspecialchars((string)$c['char_name'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="char-class"><?= htmlspecialchars((string)$c['class_name'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="char-meta"><span>Nível <b><?= (int)$c['level'] ?></b></span><span>Fama <b><?= number_format((float)$c['fame'], 0, ',', '.') ?></b></span></div>
      </button>
    <?php endforeach; ?>
  </div>
  <?php foreach ($dadosCompletos as $i => $c):
    $maxHp = max(0, (float)$c['maxHp']); $maxCp = max(0, (float)$c['maxCp']); $maxMp = max(0, (float)$c['maxMp']);
    $hpPct = $maxHp > 0 ? min(100, max(0, (float)$c['curHp'] / $maxHp * 100)) : 0;
    $cpPct = $maxCp > 0 ? min(100, max(0, (float)$c['curCp'] / $maxCp * 100)) : 0;
    $mpPct = $maxMp > 0 ? min(100, max(0, (float)$c['curMp'] / $maxMp * 100)) : 0;
    $playHours = floor(max(0, (float)$c['onlinetime']) / 3600);
  ?>
  <section class="character-view <?= $i === 0 ? 'active' : '' ?>" data-view="<?= (int)$i ?>">
    <div class="hero">
      <div class="hero-copy">
        <div class="rune-line">Campeão selecionado</div>
        <h3 class="hero-name"><?= htmlspecialchars((string)$c['char_name'], ENT_QUOTES, 'UTF-8') ?></h3>
        <div class="hero-class"><?= htmlspecialchars((string)$c['class_name'], ENT_QUOTES, 'UTF-8') ?></div>
        <p class="hero-title"><?= trim((string)$c['title']) !== '' ? htmlspecialchars((string)$c['title'], ENT_QUOTES, 'UTF-8') : 'Seu nome será lembrado nas crônicas de Aden.' ?></p>
        <div class="hero-chips"><span class="chip">NÍVEL <strong><?= (int)$c['level'] ?></strong></span><span class="chip">CLÃ <strong><?= htmlspecialchars((string)($c['clan'] ?: 'Sem clã'), ENT_QUOTES, 'UTF-8') ?></strong></span><span class="chip"><?= !empty($c['online']) ? '● ONLINE' : '○ OFFLINE' ?></span></div>
      </div>
      <div class="warrior-art">
        <div class="magic-ring"></div><span class="rune r1">ᚱ</span><span class="rune r2">ᛟ</span><span class="rune r3">ᚷ</span><span class="rune r4">ᛉ</span>
        <model-viewer class="warrior-model" id="warriorModel-<?= (int)$i ?>" src="https://threejs.org/examples/models/gltf/Soldier.glb" alt="Avatar 3D de demonstração do Virtual Boost" camera-controls auto-rotate autoplay shadow-intensity="1" exposure="1.1" interaction-prompt="none" environment-image="neutral" loading="lazy"></model-viewer>
        <div class="avatar-controls" role="group" aria-label="Escolher sexo e visual do avatar">
          <div class="avatar-controls-label">Escolha o avatar</div>
          <button type="button" class="avatar-control active" data-avatar-look="male" data-model="warriorModel-<?= (int)$i ?>" aria-pressed="true">♂ Homem</button>
          <button type="button" class="avatar-control" data-avatar-look="female" data-model="warriorModel-<?= (int)$i ?>" aria-pressed="false">♀ Mulher</button>
          <button type="button" class="avatar-control" data-avatar-look="armor" data-model="warriorModel-<?= (int)$i ?>" aria-pressed="false">⚔ Armadura</button>
        </div>
      </div>
    </div>
    <div class="section-heading"><h3>Relíquias e atributos</h3><span>Dados do personagem</span></div>
    <div class="stats-grid" id="atributos">
      <article class="stat-card"><span class="stat-icon">◈</span><div class="stat-label">Nível</div><div class="stat-value"><?= number_format((float)$c['level'], 0, ',', '.') ?></div><div class="stat-foot">Classe principal</div></article>
      <article class="stat-card"><span class="stat-icon">✧</span><div class="stat-label">Adena</div><div class="stat-value"><?= $c['adena'] === null ? '—' : number_format((float)$c['adena'], 0, ',', '.') ?></div><div class="stat-foot">Moeda do reino</div></article>
      <article class="stat-card"><span class="stat-icon">⚔</span><div class="stat-label">Pontos PvP</div><div class="stat-value"><?= number_format((float)$c['pvpkills'], 0, ',', '.') ?></div><div class="stat-foot">Vitórias em combate</div></article>
      <article class="stat-card">
    <span class="stat-icon">💰</span>
    <div class="stat-label">Donate Coins</div>
    <div class="stat-value">
<?= $c['donateCoins'] === null ? '—' : number_format((int)$c['donateCoins'], 0, ',', '.') ?>
    </div>
    <div class="stat-foot">Moedas Donate no inventário</div>
</article>
    </div>
    <div class="section-heading"><h3>Estado de combate</h3><span>HP · CP · MP</span></div>
    <div class="details-grid">
      <article class="panel"><h4>✦ Energia vital</h4>
        <div class="bar-row"><div class="bar-head"><span>HP · Vida</span><span><?= number_format((float)$c['curHp'], 0, ',', '.') ?> / <?= number_format($maxHp, 0, ',', '.') ?></span></div><div class="bar"><div class="bar-fill hp" data-width="<?= $hpPct ?>"></div></div></div>
        <div class="bar-row"><div class="bar-head"><span>CP · Combate</span><span><?= number_format((float)$c['curCp'], 0, ',', '.') ?> / <?= number_format($maxCp, 0, ',', '.') ?></span></div><div class="bar"><div class="bar-fill cp" data-width="<?= $cpPct ?>"></div></div></div>
        <div class="bar-row"><div class="bar-head"><span>MP · Mana</span><span><?= number_format((float)$c['curMp'], 0, ',', '.') ?> / <?= number_format($maxMp, 0, ',', '.') ?></span></div><div class="bar"><div class="bar-fill mp" data-width="<?= $mpPct ?>"></div></div></div>
      </article>
      <article class="panel" id="detalhes"><h4>✦ Crônicas do campeão</h4><div class="info-list">
        <div class="info-item"><span>Clã</span><strong><?= htmlspecialchars((string)($c['clan'] ?: 'Sem clã'), ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="info-item"><span>Estado</span><strong><?= !empty($c['online']) ? 'Online' : 'Offline' ?></strong></div>
		<div class="info-item"><span>PvP</span><strong><?= number_format((int)($c['pvpkills'] ?? 0), 0, ',', '.') ?></strong></div>
        <div class="info-item"><span>PK</span><strong><?= number_format((float)$c['pkkills'], 0, ',', '.') ?></strong></div>
        <div class="info-item"><span>Fama</span><strong><?= number_format((float)$c['fame'], 0, ',', '.') ?></strong></div>
        <div class="info-item"><span>Karma</span><strong><?= number_format((float)$c['karma'], 0, ',', '.') ?></strong></div>
        <div class="info-item"><span>Raid bosses</span><strong><?= number_format((float)$c['raidkills'], 0, ',', '.') ?></strong></div>
        <div class="info-item"><span>Recomendações</span><strong><?= number_format((float)$c['rec_have'], 0, ',', '.') ?> / <?= number_format((float)$c['rec_left'], 0, ',', '.') ?></strong></div>
        <div class="info-item"><span>Vitalidade</span><strong><?= number_format((float)$c['vitality'], 0, ',', '.') ?></strong></div>
        <div class="info-item"><span>Tempo online</span><strong><?= number_format((float)$playHours, 0, ',', '.') ?> h</strong></div>
      </div></article>
    </div>
    <div class="section-heading" id="subclasses"><h3>Árvore de subclasses</h3><span><?= count($c['subclasses'] ?? []) ?> registradas</span></div>
    <?php if (!empty($c['subclasses'])): ?><div class="subclasses">
      <?php foreach ($c['subclasses'] as $sub): $subId = (int)($sub['class_id'] ?? 0); ?>
      <article class="subclass"><div class="subclass-top"><span class="subclass-name"><?= htmlspecialchars((string)($class_names[$subId] ?? ('Classe ID '.$subId)), ENT_QUOTES, 'UTF-8') ?></span><span class="level-badge">Nv. <?= (int)($sub['level'] ?? 1) ?></span></div><small><?= !empty($sub['active']) ? '✦ Subclasse ativa' : 'Subclasse registrada' ?></small></article>
      <?php endforeach; ?>
    </div><?php else: ?><div class="empty">Este personagem ainda não possui subclasses registradas.</div><?php endif; ?>
  </section>
  <?php endforeach; ?>
  <?php endif; ?>
  <footer class="footer"><div><span>✦ L2 VIRTUAL BOOST</span> · Painel do jogador</div><div>Que as crônicas de Aden registrem sua lenda.</div></footer>
</main>
</div>
<script>
(function(){
  const buttons = Array.from(document.querySelectorAll('[data-select]'));
  const views = Array.from(document.querySelectorAll('[data-view]'));
  function selectCharacter(index){
    buttons.forEach((button)=>{const active=Number(button.dataset.select)===index;button.classList.toggle('selected',active);button.setAttribute('aria-pressed',active?'true':'false');});
    views.forEach((view)=>{const active=Number(view.dataset.view)===index;view.classList.toggle('active',active);if(active)view.querySelectorAll('.bar-fill').forEach((bar)=>{bar.style.width=(Number(bar.dataset.width)||0)+'%';});});
  }
  buttons.forEach((button)=>button.addEventListener('click',()=>selectCharacter(Number(button.dataset.select))));
  const avatarLooks = {
    male: { src: 'https://pub-2534e921bf9c4314addcd4d8a6e98b7b.r2.dev/avatars/mixamo/glb/y-bot.glb', alt: 'Avatar masculino 3D com roupa simples' },
    female: { src: 'https://www.innerscene.com/api/library/low-poly-character-casual-female-walking-static-befaef7b/download', alt: 'Avatar feminino 3D com roupa simples' },
    armor: { src: 'https://threejs.org/examples/models/gltf/Soldier.glb', alt: 'Guerreiro 3D com armadura medieval' }
  };
  document.querySelectorAll('[data-avatar-look]').forEach((button)=>{
    button.addEventListener('click',()=>{
      const viewer=document.getElementById(button.dataset.model);
      const look=avatarLooks[button.dataset.avatarLook];
      if(!viewer||!look)return;
      viewer.src=look.src;
      viewer.alt=look.alt;
      // Mantém a reprodução das animações incluídas no GLB; sem autoplay, alguns rigs ficam em pose de cruz/T.
      viewer.setAttribute('autoplay','');
      viewer.setAttribute('auto-rotate','');
      const controls=button.closest('.avatar-controls');
      controls.querySelectorAll('[data-avatar-look]').forEach((item)=>{
        const active=item===button;
        item.classList.toggle('active',active);
        item.setAttribute('aria-pressed',active?'true':'false');
      });
    });
  });
  const first=views.find((v)=>v.classList.contains('active'));if(first)first.querySelectorAll('.bar-fill').forEach((bar)=>{bar.style.width=(Number(bar.dataset.width)||0)+'%';});
  const sidebar=document.getElementById('sidebar'),toggle=document.getElementById('menuToggle');
  if(toggle&&sidebar){toggle.addEventListener('click',()=>sidebar.classList.toggle('open'));sidebar.querySelectorAll('a').forEach((a)=>a.addEventListener('click',()=>sidebar.classList.remove('open')));document.addEventListener('keydown',(e)=>{if(e.key==='Escape')sidebar.classList.remove('open');});}
})();
</script>
</body>
</html>
