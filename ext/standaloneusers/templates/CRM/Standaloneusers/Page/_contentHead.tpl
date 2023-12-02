<style>
{literal}
/***Structure****
    Variables (comment out your subtheme)
        - Finsbury Park
        - Jerry Seinfeld
        - Shoreditch (soon)
        - Aah (soon)
    Resets
    Base
****************/

/***************
    Variables
****************/

/* Finsbury Park

:root {
    --roundness: 0.25rem;
    --font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans, Ubuntu,Cantarell,"Helvetica Neue",Helvetica,Arial,sans-serif,"Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
    --text-colour: #000;
    --text-size: 0.9rem;
    --error-colour: #aa0c0c;
    --label-colour: #000;
    --background-colour: #ededed;
    --box-border: 1px #cdcdcd solid;
    --box-padding: 2rem 1.75rem;
    --box-shadow: none;
    --box-roundness: 0.25rem;
    --box-background: #fff;
    --input-border: 1px solid #ccc;
    --input-padding: 0.5rem;
    --input-shadow: inset 0 1px 1px rgba(0,0,0,.075);
    --button-border: 1px solid #bbb;
    --button-shadow: 0 1px 2px rgba(0,0,0,0.05);
    --button-padding: 5px 15px;
    --button-text-colour: #3e3e3e;
    --button-background: #f0f0f0;
}

/* Shoreditch

:root {
    --roundness: 2px;
    --font-family: "Open Sans","Helvetica Neue",Helvetica,Arial,sans-serif;
    --text-colour: #232429;
    --text-size: 0.9rem;
    --error-colour: #cf3458;
    --label-colour: #464354;
    --background-colour: #f3f6f7;
    --box-border: 0 transparent solid;
    --box-padding: 20px;
    --box-shadow: 0 3px 18px 0 rgba(48,40,40,0.25);
    --box-roundness: 2px;
    --box-background: #fff;
    --input-border: 1px solid #c2cfd8;
    --input-padding: 5px 10px;
    --input-shadow: inset 0 0 3px 0 rgba(0,0,0,0.2);
    --button-border: 0 solid transparent;
    --button-shadow: none;
    --button-padding: 8px 28px;
    --button-text-colour: #fff;
    --button-background: #0071bd;
}

/* Aah */

:root {
    --roundness: 3px;
    --font-family: Lato,Helvetica,Arial,sans-serif;
    --font-size-h1: 1.6rem;
    --text-colour: #222;
    --text-size: 0.9rem;
    --error-colour: #a00;
    --warning-colour: #fbb862;
    --success-colour: #86c66c;
    --label-colour: #464354;
    --background-colour: rgb(242,242,237);
    --box-border: 0 transparent solid;
    --box-padding: 1.6rem;
    --box-shadow: none;
    --box-roundness: 0;
    --box-background: #fff;
    --input-border: 1px solid rgba(0,0,0,.2);
    --input-padding: 5px 10px;
    --input-shadow: inset 0 0 3px 0 rgba(0,0,0,0.2);
    --button-border: 0 solid transparent;
    --button-shadow: 0 0 6px rgba(0,0,0,.2);
    --button-padding: .4rem 1.6rem;
    --button-text-colour: #fff;
    --button-background: #2c98ed;
    --button-text-shadow: none;
}

/* Ffresh

:root {
    --roundness: 2rem;
    --font-family: Lato,Helvetica,Arial,sans-serif;
    --text-colour: #222;
    --text-size: 1rem;
    --error-colour: #a00;
    --label-colour: #464354;
    --background-colour: #2c98ed;
    --box-border: 0 transparent solid;
    --box-padding: 1.6rem;
    --box-shadow: 0 0 10px 0 rgba(0,0,0,0.2);
    --box-roundness: 1.75rem;
    --box-background: #fff;
    --input-border: 2px solid #2c98ed;
    --input-padding: 0.75rem;
    --input-shadow: none;
    --button-border: 0 solid transparent;
    --button-shadow: none;
    --button-padding: 0.75rem 2rem;
    --button-text-colour: #fff;
    --button-background: #2c98ed;
}

/***************
    Base
****************/

body {
    background-color: var(--background-colour);
    font-family: var(--font-family);
    color: var(--text-colour);
    font-size: var(--text-size);
    padding: 0;
    margin: 0;
}
#crm-container.standalone-entry {
    display: grid;
    place-content: center;
    width: 100%;
    height: 100vh;
}
#crm-container.standalone-entry * {
    box-sizing: border-box;
}
a {
    text-decoration: none;
    font-size: 90%;
}
a:hover, a:focus {
    text-decoration: underline;
}
.flex {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

h1 {
  font-family: var(--font-family);
  font-size: var(--font-size-h1);
}

/***************
    UI Elements
****************/

#crm-container.standalone-entry .mid-block {
    width: clamp(280px, 68vw, 34rem);
    margin: 0;
    background-color: var(--box-background);
    border: var(--box-border);
    border-radius: var(--box-roundness);
    padding: var(--box-padding);
    box-shadow: var(--box-shadow);
}
#crm-container.standalone-entry img {
    width: 100%;
    max-width: 400px;
    margin: 0 auto 2rem;
    display: block;
}
#crm-container.standalone-entry label {
    display: inline-block;
    max-width: 100%;
    margin-bottom: 5px;
    font-weight: 700;
    color: var(--label-colour);
}
#crm-container.standalone-entry input {
    display: block;
    width: 100%;
    color: #555;
    background-color: #fff;
    background-image: none;
    margin-bottom: 0.75rem;
    padding: var(--input-padding);
    font-size: var(--text-size);
    border-radius: var(--roundness);
    border: var(--input-border);
    box-shadow: var(--input-shadow);
}
#crm-container.standalone-entry input:focus,
#crm-container.standalone-entry input:focus-visible {
    border: 1px solid #66afe9;
}
#crm-container.standalone-entry .btn {
    display: inline-block;
    margin:0;
    text-align: center;
    vertical-align: middle;
    touch-action: manipulation;
    cursor: pointer;
    background-image: none;
    font-size: var(--text-size);
    background-color: var(--button-background);
    color: var(--button-text-colour);
    border: var(--button-border);
    padding: var(--button-padding);
    border-radius: var(--roundness);
    font-family: var(--font-family);
    box-shadow: var(--button-shadow);
    text-shadow: var(--button-text-shadow);
}
#crm-container.standalone-entry .btn:hover,
#crm-container.standalone-entry .btn:focus {
    filter: brightness(80%);
}
#crm-container.standalone-entry .float-right {
    float: right;
    font-size: 90%;
    margin-top: 0.2rem;
}
#crm-container.standalone-entry .form-alert {
    color: var(--error-colour);
    margin: 1rem 0;
}


#loggedOutNotice {
  text-align: center;
  font-weight: bold;
  padding: var(--box-padding);
  background-color: var(--success-colour);
  margin: 1rem 0;
  border-radius: var(--box-roundness);
}
#anonAccessDenied {
  text-align: center;
  font-weight: bold;
  padding: var(--box-padding);
  background-color: var(--warning-colour);
  margin: 1rem 0;
  border-radius: var(--box-roundness);
}

/*
@media  (min-width: 768px) {
    #crm-container.standalone-entry {
        width: 60vw;
        margin: 20vh auto 0;
    }
}
@media  (min-width: 960px) {
    #crm-container.standalone-entry {
        width: 30vw;
    }
}
*/
{/literal}
</style>

<div id="crm-container" class="crm-container standalone-entry">
  <div class="mid-block">
    <img src="{$logoUrl}" alt="logo for CiviCRM, with an intersecting blue and green triangle">

