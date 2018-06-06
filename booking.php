<?php require_once('dbcon.php');
// Nedenstående kode inspireret af: https://www.youtube.com/watch?v=rDsdBAAXkAU
// Sætter tidszonen
date_default_timezone_get('Europe/Copenhagen');

// Hent 'prev' og 'next' måned
if (isset($_GET['ym'])) {
    $ym = $_GET['ym'];
} else {
    // Denne mned
    $ym = date('Y-m');
}
// Tjekker formattet
$timestamp = strtotime($ym,"-01");
if ($timestamp === false) {
    $timestamp = time();
}

// I dag (2018 - 05 - 22)
$today = date('Y-m-d', time());

// H2 titlen
$html_title = date('Y / m', $timestamp);
$monthyear = date('m - y', $timestamp);
$format = preg_replace('/\s+/', '', $monthyear);


// "Prev" og "next" mned links  mktime(time, minute, sek, måned, dag, r)
$prev = date('Y-m', mktime(0, 0, 0, date('m', $timestamp)-1, 1, date('Y', $timestamp)));
$next = date('Y-m', mktime(0, 0, 0, date('m', $timestamp)+1, 1, date('Y', $timestamp)));

// Antallet af dage på en måned
$day_count = date('t', $timestamp);

// 0:Sn 1:Man 2:Tirs...
$str = date('w', mktime(0, 0, 0, date('m', $timestamp)-1, 1, date('Y', $timestamp)));

// Laver kalenderen
$weeks = array();
$week = '';

// Henter alle 'bookede' datoer i Databasen ind i et array
$hentBookinger = mysqli_query($conn, "SELECT * FROM wp_kontakt");
$bookinger = array(); //tomt array

while($row = mysqli_fetch_array($hentBookinger)){
    $dato = $row["dato"];
    // Henter datoerne i det rigtige format
    list($day, $month, $year) = explode("-", $dato);
    $ymd = "$year-$month-$day";
    $datoFormat = date("Y-m-d", strtotime($ymd));

    // datoFormat skubbes ind i tomme array 
    array_push($bookinger, $datoFormat);
}

// Tilføjer tom celle
$week .= str_repeat('<td></td>', $str);

for ( $day = 1; $day <= $day_count; $day++, $str++) {

    $date = $ym.'-'.$day;
    $dagsDato = $ym . "-" . $day;

    // parser string til tid - konverterer til sekunder 
    $datoitid = strtotime($dagsDato);
    $dagsdatoitid = strtotime($today);

    // Hvis dagsdato = i dag og hvis dagene før i dag er mindre end i dag...
    if ($today == $date) {
        if(in_array($dagsDato, $bookinger)){
                $week .= '<td class="today todayBooket" dag="'. $day .'-'. $format .'">'.$day . "<br>Booket";
            } else {
                $week .= '<td class="today" dag="'. $day .'-'. $format .'" id="ledig">'.$day;
            }
    } else {
        if($datoitid < $dagsdatoitid)
        {
            if(in_array($dagsDato, $bookinger)){
                $week .= '<td class="inaktiv booket" dag="'. $day .'-'. $format .'">'.$day . "<br>Booket";
            } else {
                $week .= '<td class="inaktiv" dag="'. $day .'-'. $format .'">'.$day;
            }
        } else {
            if(in_array($dagsDato, $bookinger)){
                $week .= '<td dag="'. $day .'-'. $format .'" class="booket">'.$day . "<br>Booket";
            } else {
                $week .= '<td dag="'. $day .'-'. $format .'" id="ledig">'.$day;
            }
        }
    }
    $week .= '</td>';
    // Slutningen på ugen eller måneden
    if ($str % 7 == 6 || $day == $day_count) {
        if($day == $day_count) {
            // Tilfjer tom celle
            $week .= str_repeat('<td></td>', 6 - ($str % 7));
        }

        $weeks[] = '<tr>'.$week.'</tr>';

        // Forbereder ny uge
        $week = '';
    }
}
?>
<?php $error = ''; $succes = ''; ?>
<?php

    if(isset($_POST['submit'])) {
        // validering af input feler 
        $navn = filter_input(INPUT_POST, 'navn', FILTER_SANITIZE_STRING)
          or die('Error: missing navn parameter');

        $skole = filter_input(INPUT_POST, 'skole', FILTER_SANITIZE_STRING)
          or die('Error: missing skole parameter');

        $klasse = filter_input(INPUT_POST, 'klasse', FILTER_SANITIZE_STRING)
          or die('Error: missing klasse parameter');

        $telefon = filter_input(INPUT_POST, 'telefon', FILTER_SANITIZE_STRING)
          or die('Error: missing telefon parameter');

        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL)
          or die('Error: missing email parameter');

        $forlob = filter_input(INPUT_POST, 'forlob', FILTER_SANITIZE_STRING)
          or die('Error: missing forlb parameter');

        $dato = filter_input(INPUT_POST, 'dato', FILTER_SANITIZE_STRING)
          or die('Error: missing dato parameter');

        $forlob_id = filter_input(INPUT_POST, 'forlob', FILTER_VALIDATE_INT)
          or die('Error: missing forlbId parameter');

          // validerer om input felterne er udfyldt
          if(empty($navn) || empty($skole) || empty($telefon) || empty($email) || empty($forlob) || empty($dato) || empty($forlob_id)) {
            echo 'Ingen felter må være tomme';
            exit();
          } elseif(!preg_match("%^[-a-zA-Z æøå ÆØÅ \s\/]+$%", $navn) || !preg_match("%^[-a-zA-Z æøå ÆØÅ \s\/]+$%", $skole) || !preg_match("%^[-a-zA-Z æøå ÆØÅ 0-9 \s\/]+$%", $forlob)) {
            echo 'Du må ikke bruge tegn eller tal';
          } else {

        // data sættes ind i Databasen
        $sql = 'INSERT INTO `wp_kontakt` (navn, skole, klasse, telefon, email, dato, forlob_id) VALUES (?,?,?,?,?,?,?)';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssssi', $navn, $skole, $klasse, $telefon, $email, $dato, $forlob_id);
        $stmt->execute();

        // Besked om succes eller fejl
        if($stmt->affected_rows >= 1){
        $succes = '<div class="booking-meddelse" id="succes">Din booking er gennemført!</div>';
        } elseif($stmt->affected_rows = 0) {
        $error = '<div class="booking-meddelse" id="fejl">Kunne ikke gennemføre din booking. Prøv venligst igen!</div>';

        }
        else { }
        // Henter oplysninger lagret i databasen gennem join
        $sqlEmail = "SELECT t1.navn, t1.skole, t1.klasse, t1.telefon, t1.email, t1.dato, t2.forlob
                FROM `wp_kontakt` t1, `wp_forlob` t2
                WHERE t1.forlob_id = t2.forlob_id";
                    $stmtEmail = $conn->prepare($sqlEmail);
                    $stmtEmail->execute();
                    $stmtEmail->bind_result($navn, $skole, $klasse, $telefon, $email, $dato, $forlob);
                    while($stmtEmail->fetch()) {};

        // Sender e-mail til virksomheden vedr. booking
        $emailTil = "charmainemc1@hotmail.com";
        $emailEmne = "Booking af skolehaveforløb";
        $emailAdresse = "kontakt@greentouch.dk";
        $emailBesked = "{$skole} har booket {$dato} i kalenderen. \n\nOplysninger om {$skole}:\nNavn: {$navn}\nKlasse: {$klasse}\nTelefon: {$telefon}\nE-mail: {$email}\nForløb: {$forlob}\nDato: {$dato}";

        $headers = 'From: ' . $emailAdresse . '' . "\r\n" .
        'Reply-To: ' . $email . '' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
        mail($emailTil, $emailEmne, $emailBesked, $headers);  
    }
};

?>

</head>

<body>
    <!-- Displayer submit besked  -->
        <?php echo $succes; ?>
        <?php echo $error; ?>

    <div  class="cal-container">
        <h3><a id="prev" href="?ym=<?php echo $prev; ?>">&#10094; </a><?php echo $html_title; ?> <a id="next" href="?ym=<?php echo $next; ?>"> &#10095;</a></h3>
            <div class="cal-frame">
                <table class="table table-bordered" id="kalender">
                    <tr>
                        <th>SØN</th>
                        <th>MAN</th>
                        <th>TIRS</th>
                        <th>ONS</th>
                        <th>TORS</th>
                        <th>FRE</th>
                        <th>LØR</th>
                    </tr>
                <!-- Laver kalender (datoer) vha loop  -->
                   <?php
                         foreach ($weeks as $week) {
                            echo $week;
                         }

                   ?>
                </table>

            </div>
    </div>

<!-- Modal-boksen -->
<div id="MINmodal" class="MINmodal">

  <!-- Modal-boks indholdet med form -->
  <div class="MITmodal-content">
    <span class="lukKnap">×</span>
    <div id="h2box"><h2>DU ER VED AT BOOKE DATO:</h2><h2 id="headline"></h2></div>
    <div id="form-container">
    <form id="MINform" method="post" action="<?php $_SERVER['PHP_SELF']?>" >
        <input type="text" placeholder="Navn:" name="navn" data-validation="length alphanumeric" data-validation-ignore="Æ Ø Å æ ø å" data-validation-allowing="-_" data-validation-length="max50" minlength="2" data-validation-error-msg="Dit navn må kun indeholde bogstaver. Prøv venligst igen!" required autocomplete="on">

        <input type="text" placeholder="Skole:" name="skole" data-validation-ignore="Æ Ø Å æ ø å" data-validation-error-msg="Skolens navn må kun indeholde bogstaver. Prøv venligst igen!" data-validation-allowing="-_" id="skole" data-validation="length alphanumeric" data-validation-length="max50" minlength="2" required>

        <input type="text" placeholder="Klassetrin:" data-validation-error-msg="Feltet m hjst vre 4 karakterer langt. F.eks.: 3.R. Prøv venligst igen!" name="klasse" data-validation-ignore="Æ Ø Å æ ø å ." data-validation="length alphanumeric" data-validation-length="max4" id="klasse" required>

        <input type="number" placeholder="Telefon:" name="telefon" data-validation-length="8-10"  data-validation-error-msg="Telefonnummeret skal indeholde 8-10 cifre. F.eks.: 1234 5678. Prøv venligst igen!"  data-validation="number length" required autocomplete="on">

        <input type="email" placeholder="Email:" name="email" data-validation="email" required autocomplete="on" data-validation-error-msg="E-mail er ikke korrekt skrevet. F.eks.: ditnavn@gmail.com. Prøv venligst igen!">
        <input type="hidden" name="dato" id="datohidden" value="">

        <select type="text" name="forlob" required>
            <option selected disabled>Vælg forløb *</option>
            <!-- Henter forløb i Databasen og displayer det -->
            <?php
                  $sql2="SELECT forlob_id, forlob FROM `wp_forlob`";
                  $stmt2 = $conn->prepare($sql2); //fat i vores database
                  $stmt2->execute(); //s bliver det exekveret
                  $stmt2->bind_result($forlob_id, $forlob); //binde vores resultater med to variabler

                  while($stmt2->fetch()) { //mens det bliver kørt i gennem skal vi exekvere noget
                    echo '
                    <option value="'.$forlob_id.'">'.$forlob.'</option>';
                }
                  ?>
        </select>
    <div id="knapper">
        <input class="submit" id="book" type="submit" name="submit" value="BOOK DAG">
        <button id="annuller">ANNULLER</button>
    </div>
    </form>
    </div>
  </div>

</div>
<!-- JQuery validering - Form validator links -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery-form-validator/2.3.26/jquery.form-validator.min.js"></script>
  <script>
        $.validate({});
  </script>
</body>
</html>
