function submitForm() {
  var firstName = (document.getElementById('firstName') || {}).value || '';
  var lastName = (document.getElementById('lastName') || {}).value || '';
  var email = (document.getElementById('email') || {}).value || '';
  var jobTitle = (document.getElementById('jobTitle') || {}).value || '';
  var bio = (document.getElementById('bioLong') || {}).value || '';

  if (!firstName.trim() || !lastName.trim() || !email.trim()) {
    alert('Please fill First Name, Last Name and Email.');
    return;
  }

  var data = {
    firstName: firstName.trim(),
    lastName: lastName.trim(),
    email: email.trim(),
    jobTitle: jobTitle.trim(),
    bio: bio.trim()
  };

  console.log(data);
  alert('Data saved in browser console (demo).');
}
