document.getElementById('menu-btn').addEventListener('click', function() {
    document.getElementById('sidebar').style.width = '250px';
});

document.getElementById('close-btn').addEventListener('click', function() {
    document.getElementById('sidebar').style.width = '0';
});