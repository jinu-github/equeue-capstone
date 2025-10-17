import { useState, useEffect } from 'react';
import Head from 'next/head';
import styles from '../styles/globals.css';

export default function Home() {
  const [queueData, setQueueData] = useState('<div class="loading"><div class="spinner"></div>Loading queue data...</div>');
  const [lastUpdated, setLastUpdated] = useState('Loading...');

  useEffect(() => {
    const loadQueue = () => {
      fetch('/api/queue')
        .then(response => response.text())
        .then(data => {
          setQueueData(data);
          setLastUpdated('Last updated: ' + new Date().toLocaleTimeString());
        })
        .catch(error => {
          setQueueData('<div class="alert alert-error">Failed to load queue data. Please refresh the page.</div>');
        });
    };

    loadQueue();
    const interval = setInterval(loadQueue, 5000);
    return () => clearInterval(interval);
  }, []);

  return (
    <div className="container">
      <Head>
        <title>eQueue - Live Queue Display</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      </Head>

      <header>
        <h1>Live Queue Status</h1>
        <div className="header-nav">
          <span id="last-updated" style={{ opacity: 0.8, fontSize: '0.875rem' }}>{lastUpdated}</span>
        </div>
      </header>

      <main>
        <div id="queue-display" className="queue-grid" dangerouslySetInnerHTML={{ __html: queueData }} />
      </main>

      <style jsx>{`
        :root {
          --bg-color: #f8fafc;
          --card-bg: #ffffff;
          --text-color: #1e293b;
          --text-secondary: #6b7280;
          --border-color: #e2e8f0;
          --primary: #3b82f6;
          --primary-hover: #2563eb;
          --danger: #ef4444;
          --danger-hover: #dc2626;
          --success: #10b981;
          --warning: #f59e0b;
          --shadow: rgba(0, 0, 0, 0.1);
        }

        [data-theme="dark"] {
          --bg-color: #0f172a;
          --card-bg: #1e293b;
          --text-color: #f1f5f9;
          --text-secondary: #94a3b8;
          --border-color: #334155;
          --primary: #60a5fa;
          --primary-hover: #3b82f6;
          --danger: #f87171;
          --danger-hover: #ef4444;
          --success: #34d399;
          --warning: #fbbf24;
          --shadow: rgba(0, 0, 0, 0.3);
        }

        body {
          background: var(--bg-color);
          color: var(--text-color);
          transition: all 0.3s ease;
        }

        .container {
          max-width: 1200px;
          margin: 0 auto;
          padding: 2rem;
          background: linear-gradient(135deg, var(--bg-color) 0%, rgba(59, 130, 246, 0.05) 100%);
          min-height: 100vh;
        }

        header {
          background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
          color: white;
          padding: 1.5rem 2rem;
          border-radius: 16px;
          margin-bottom: 2rem;
          box-shadow: 0 8px 32px var(--shadow);
          display: flex;
          justify-content: space-between;
          align-items: center;
        }

        header h1 {
          margin: 0;
          font-size: 2rem;
          font-weight: 700;
        }

        .header-nav {
          display: flex;
          gap: 1rem;
          align-items: center;
        }

        .header-nav a {
          color: white;
          text-decoration: none;
          padding: 0.5rem 1rem;
          border-radius: 8px;
          transition: all 0.3s ease;
          font-weight: 500;
        }

        .header-nav a:hover {
          background: rgba(255, 255, 255, 0.1);
          transform: translateY(-1px);
        }

        .alert {
          padding: 1rem 1.5rem;
          border-radius: 12px;
          margin-bottom: 1.5rem;
          font-weight: 500;
          animation: bounceIn 0.5s ease-out;
        }

        @keyframes bounceIn {
          0% { opacity: 0; transform: scale(0.3); }
          50% { opacity: 1; transform: scale(1.05); }
          70% { transform: scale(0.9); }
          100% { opacity: 1; transform: scale(1); }
        }

        .alert-success {
          background: rgba(16, 185, 129, 0.1);
          color: var(--success);
          border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert-error {
          background: rgba(239, 68, 68, 0.1);
          color: var(--danger);
          border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .queue-number {
          font-size: 3rem;
          font-weight: bold;
          color: var(--primary);
          display: block;
          text-align: center;
          margin: 0.5rem 0;
        }
      `}</style>
    </div>
  );
}
