<?php ?>
<style>
  :root {
    --purple-main: #6c5ce7;
    --text-dark: #2d3436;
    --bg-main: #f8f9fa;
    --danger: #d63031;
    --success: #00b894;
    --warning: #f39c12;
  }

  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    font-family: 'Poppins', sans-serif;
    background-color: var(--bg-main);
    display: flex;
    min-height: 100vh;
  }

  .sidebar {
    width: 250px;
    background-color: #fff;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
    padding: 30px 20px;
    position: fixed;
    height: 100vh;
    transition: 0.3s;
    z-index: 100;
  }

  .sidebar h2 {
    color: var(--purple-main);
    font-size: 1.5rem;
    margin-bottom: 30px;
  }

  .sidebar a {
    display: block;
    padding: 12px;
    color: var(--text-dark);
    text-decoration: none;
    margin-bottom: 10px;
    border-radius: 8px;
    transition: 0.3s;
  }

  .sidebar a:hover,
  .sidebar a.active {
    background-color: var(--purple-main);
    color: #fff;
  }

  .main-content {
    flex: 1;
    padding: 40px;
    margin-left: 250px;
    min-height: 100vh;
    width: calc(100% - 250px);
  }

  .header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
  }

  .grid-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
  }

  .stat-card {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    text-align: center;
  }

  .stat-card h3 {
    font-size: 2.2rem;
    color: var(--purple-main);
    font-weight: 700;
    margin-top: 5px;
  }

  .chart-box {
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 40px;
    max-width: 100%;
    overflow: hidden;
  }

  /* RESPONSIVE TABLE WRAPPER */
  .table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin-bottom: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  }

  table {
    width: 100%;
    background: #fff;
    border-collapse: collapse;
    min-width: 700px;
    table-layout: fixed;
  }

  th,
  td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
    word-wrap: break-word;
    vertical-align: top;
  }

  th {
    background-color: var(--purple-main);
    color: #fff;
    font-weight: 600;
  }

  .text-truncate-cell {
    max-height: 120px;
    overflow-y: auto;
    font-size: 0.9rem;
    line-height: 1.4;
    color: #555;
    padding-right: 5px;
  }

  .btn {
    padding: 8px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    color: #fff;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    display: inline-block;
    text-align: center;
  }

  .btn-success {
    background-color: var(--success);
  }

  .btn-danger {
    background-color: var(--danger);
  }

  .btn-warning {
    background-color: var(--warning);
  }

  .btn-primary {
    background-color: var(--purple-main);
    margin-top: 10px;
  }

  .alert {
    padding: 15px;
    background-color: #ffeaa7;
    margin-bottom: 20px;
    border-radius: 8px;
  }

  .admin-form {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
  }

  .admin-form input[type="text"],
  .admin-form input[type="url"],
  .admin-form select,
  .admin-form textarea {
    width: 100%;
    padding: 10px;
    margin: 8px 0 15px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-family: 'Poppins', sans-serif;
  }

  .filter-bar {
    background: #fff;
    padding: 15px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
  }

  .filter-bar input,
  .filter-bar select {
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-family: 'Poppins', sans-serif;
    flex: 1;
    min-width: 180px;
  }

  /* RESPONSIVE PAGINATION BAR */
  .pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    padding: 12px 20px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 10px;
  }

  .pagination-info {
    font-size: 0.9rem;
    color: #636e72;
  }

  .pagination-buttons {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
  }

  .pagination-btn {
    display: inline-block;
    padding: 6px 12px;
    border: 1px solid #ddd;
    background: #fff;
    color: var(--text-dark);
    text-decoration: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 600;
    transition: 0.2s;
  }

  .pagination-btn:hover {
    background: #f1f0ff;
    border-color: var(--purple-main);
    color: var(--purple-main);
  }

  .pagination-btn.active {
    background: var(--purple-main);
    color: #fff;
    border-color: var(--purple-main);
  }

  .pagination-btn.disabled {
    background: #f8f9fa;
    color: #ccc;
    border-color: #eee;
    pointer-events: none;
  }

  /* MOBILE RESPONSIVE MEDIA QUERIES */
  @media (max-width: 992px) {
    .sidebar {
      width: 70px;
      padding: 20px 10px;
    }

    .sidebar h2 {
      display: none;
    }

    .sidebar a {
      text-align: center;
      font-size: 1.2rem;
      padding: 10px 0;
    }

    /* Hide text in sidebar links if icons are added later, or compress names */
    .sidebar a {
      font-size: 0.75rem;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .main-content {
      margin-left: 70px;
      width: calc(100% - 70px);
      padding: 20px;
    }
  }

  @media (max-width: 576px) {
    .header-row h1 {
      font-size: 1.6rem;
    }

    .filter-bar {
      flex-direction: column;
      align-items: stretch;
    }

    .filter-bar input,
    .filter-bar select,
    .filter-bar button,
    .filter-bar a {
      width: 100%;
    }

    .pagination-container {
      flex-direction: column;
      text-align: center;
    }
  }
</style>