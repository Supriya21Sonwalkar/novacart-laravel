document.querySelectorAll('form').forEach(function(form){
 var password=form.querySelector('input[name="password"]');
 var confirm=form.querySelector('input[name="password_confirmation"]');
 if(!password||!confirm)return;
 function check(){confirm.setCustomValidity(confirm.value && confirm.value!==password.value?'Passwords must match.':'');}
 password.addEventListener('input',check);confirm.addEventListener('input',check);
});
