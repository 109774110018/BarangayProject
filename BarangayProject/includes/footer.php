  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Auto-dismiss alerts
    document.querySelectorAll('.alert-dismissible').forEach(a => {
      setTimeout(() => { if(a) a.classList.add('fade'); }, 4000);
    });
    // Confirm delete helper
    function confirmDelete(msg) {
      return confirm(msg || 'Are you sure you want to delete this?');
    }
  </script>
</body>
</html>
