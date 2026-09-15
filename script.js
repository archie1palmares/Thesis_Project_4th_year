const signUpButton = document.getElementById('signupBtn');
const signInButton = document.getElementById('loginBtn');
const signInForm = document.getElementById('login');
const signUpForm = document.getElementById('signup');

signUpButton.addEventListener('click', function(){
    signInForm.style.display = 'none';
    signUpForm.style.display = 'block';
})
signInButton.addEventListener('click', function(){
    signInForm.style.display = 'block';
    signUpForm.style.display = 'none';
})

