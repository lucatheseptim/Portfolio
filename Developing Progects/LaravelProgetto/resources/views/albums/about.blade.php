@extends('templates.layout')
@section('content')
<div class="about-section paddingTB60 gray-bg">
            <div class="container">
                    <div class="row">
						<div class="col-md-7 col-sm-6">
							<div class="about-title clearfix">
								<h1>About <span>ME</span></h1>
								<h3>Mi chiamo Luca Airoldi </h3>
								<p class="about-paddingB">Web Developer Full Stack come lavoro utilizzo il framework MVC Laravel mi occupo sia di 
									<p>Client-side:Javascript,Bootstrap 4(framework),Html5,Css3</p>
								<p>che Back-end: PHP <p>programmatore 24 ore su 24 </p>
                                <p>DBMS: MYSQL</p>
                                <p class="about-paddingB">ALTRE SKILLS:
                                ---Programmazione JAVA e Creazione Interfecce Grafiche( GUI
                                Graphical User Interface) realizzate durante il percorso
                                universitario.Programmazione lato Server e Client Web con
                                JAVASCRIPT,JQUERY,,AJAX---Programmazione Distribuita con
                                applicazioni CLIENT-SERVER ---Programmazione Dinamica
                                PHP,CSS,HTML,BOOTSTRAP---Programmazione ANDROID in
                                ambiente Android-Studio. ---Programmazione
                                MICROCONTROLLORI: conoscenza delle basi di elettronica in
                                generale e realizzazione progetto tramite Hardware ARDUINO e
                                relativo ambiente di sviluppo.---Linguaggio SQL(Structure Query
                                Language) e utilizzo dei principali
                                DBMS(DatabaseManagementSystem): PostGres e MySQL acquisiti
                                ---Utilizzo e conoscenza del linguaggio UML
                                (unified modeling language).---Utilizzo di XML, XmlSchema,
                                DTD(document Type Definition).---Conoscenza del Linguaggio PHP
                               ---Framework
                                conosciuti: LARAVEL(framework utilizzato per PHP).
                                </p>
							</div>
					<div class="about-icons"> 
                            <ul >
                                <li><a href="https://www.facebook.com/"><i id="social-fb" class="fa fa-facebook-square fa-3x social"></i></a> </li>
                                <li><a href="https://twitter.com/"><i id="social-tw" class="fa fa-twitter-square fa-3x social"></i></a> </li>
                                <li> <a href="https://plus.google.com/"><i id="social-gp" class="fa fa-google-plus-square fa-3x social"></i></a> </li>
                                <li> <a href="mailto:bootsnipp@gmail.com"><i id="social-em" class="fa fa-envelope-square fa-3x social"></i></a> </li>
                            </ul>       
            </div>
</div>
<style>
    .paddingTB60 {padding:60px 0px 60px 0px;}
    .gray-bg {background: #F1F1F1 !important;}
    .about-title {}
    .about-title h1 {color: #535353; font-size:45px;font-weight:600;}
    .about-title span {color: #AF0808; font-size:45px;font-weight:700;}
    .about-title h3 {color: #535353; font-size:23px;margin-bottom:24px;}
    .about-title p {color: #7a7a7a;line-height: 1.8;margin: 0 0 15px;}
    .about-paddingB {padding-bottom: 12px;}
    .about-img {padding-left: 57px;}

    /* Social Icons */
    .about-icons {margin:48px 0px 48px 0px ;}
    .about-icons i{margin-right: 10px;padding: 0px; font-size:35px;color:#323232;box-shadow: 0 0 3px rgba(0, 0, 0, .2);}
    .about-icons li {margin:0px;padding:0;display:inline-block;}
</style>
@endsection	