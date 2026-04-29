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

  <!-- Chatbot -->
<script
  src="https://www.tuqlas.com/chatbot.js"
  data-key="tq_live_3dffb1f6fc1579a1bb36f5f71ee4c31529d20756"
  data-api="https://www.tuqlas.cxom"
  defer                               
></script>

</body>
</html>
