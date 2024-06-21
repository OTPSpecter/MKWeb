"use strict";
window.addEventListener("DOMContentLoaded", (event) => {
  // menu general
  const LeMenu = document.getElementById("LeMenu");
  const CmdMenu = document.getElementById("CmdMenu");
  const CmdMenuClose = document.getElementById("CmdMenuClose");

  const btnDisconnect = document.getElementById("disconnect");

  // menu general

  CmdMenu.addEventListener("click", function () {
    if (LeMenu.style.display === "none") {
      LeMenu.style.display = "";
      CmdMenu.src = "/html/img/fermer.webp"; // Change l'icône en icône de fermeture
    } else {
      LeMenu.style.display = "none";
      CmdMenu.src = "/html/img/menu.webp"; // Change l'icône en icône de menu
    }
  });
  window.onload = function () {
    var ww = window.innerWidth;
    LeMenu.style.display = ww > 530 ? "" : "none";
    CmdMenu.style.display = ww > 530 ? "none" : "";
  };
  window.onresize = function () {
    var ww = window.innerWidth;
    LeMenu.style.display = ww > 530 ? "" : "none";
    CmdMenu.style.display = ww > 530 ? "none" : "";
  };
});
