"use strict";function handleEbanxForm(e,n){var a=function(e){return document.getElementById(e)},t=null,r=a("ebanx_"+n+"_"+e+"_"+n+"_name"),u=a("ebanx_"+n+"_"+e+"_"+n+"_number"),_=a("ebanx_"+n+"_"+e+"_expiration"),d=a("ebanx_"+n+"_"+e+"_expiration_yr"),i=a("ebanx_"+n+"_"+e+"_"+n+"_cid"),l=a("ebanx_"+n+"_"+e+"_token"),v=a("ebanx_"+n+"_"+e+"_brand"),o=a("ebanx_"+n+"_"+e+"_masked_card_number"),c=a("ebanx_"+n+"_"+e+"_device_fingerprint"),b=a("ebanx_"+n+"_"+e+"_mode"),s=a("ebanx_"+n+"_"+e+"_integration_key"),g=a("ebanx_"+n+"_"+e+"_country"),m=void 0!==a("payment_form_ebanx_"+n+"_"+e),x="sandbox"===b.value?"test":"production";EBANX.config.setMode(x),EBANX.config.setPublishableKey(s.value),EBANX.config.setCountry(g.value);var E=function(){f()||h()},f=function(){return!(u.value.length&&r.value.length&&_.value.length&&d.value.length&&i.value.length)},h=function(){if(!t){var e=document.querySelector("#review-buttons-container > button");void 0!==e&&e&&(e.disabled=!0),EBANX.card.createToken({card_number:parseInt(u.value.replace(/ /g,"")),card_name:r.value,card_due_date:(parseInt(_.value)||0)+"/"+(parseInt(d.value)||0),card_cvv:i.value},p)}},p=function(e){if(e.data.hasOwnProperty("status")){t=e.data,l.value=t.token,v.value=t.payment_type_code,o.value=t.masked_card_number,c.value=t.deviceId;var n=document.querySelector("#review-buttons-container > button");void 0!==n&&n&&(n.disabled=!1)}else;},y=function(){t=null,l.value="",v.value="",o.value="",c.value=""};m&&(r.addEventListener("blur",E,!1),u.addEventListener("blur",E,!1),_.addEventListener("blur",E,!1),d.addEventListener("blur",E,!1),i.addEventListener("blur",E,!1),r.addEventListener("change",y,!1),u.addEventListener("change",y,!1),_.addEventListener("change",y,!1),d.addEventListener("change",y,!1),i.addEventListener("change",y,!1))}
//# sourceMappingURL=checkout.js.map
