const express = require('express');
const axios = require('axios');
const cors = require('cors');

const app = express();
const PORT = 8080;
const LM_STUDIO_API = 'http://127.0.0.1:1234';
const TIMEOUT = 180000; // 180 seconds timeout

// Enable CORS with more specific configuration
app.use(cors({
  origin: ['http://localhost:3000', 'http://127.0.0.1:3000'], // Frontend URLs
  methods: ['GET', 'POST', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization']
}));
app.use(express.json());

// Log all requests
app.use((req, res, next) => {
  console.log(`[${new Date().toISOString()}] ${req.method} ${req.url}`);
  console.log('Headers:', JSON.stringify(req.headers, null, 2));

  if (req.method !== 'OPTIONS' && req.body) {
    console.log('Request body:', JSON.stringify(req.body, null, 2));
  }

  next();
});

// Specific endpoint for chat completions
app.post('/api/chat/completions', async (req, res) => {
  try {
    // Make sure we have the required fields
    if (!req.body.model || !req.body.messages) {
      return res.status(400).json({
        error: 'Missing required fields',
        message: 'The request must include model and messages fields'
      });
    }

    console.log(`Forwarding to LM Studio at: ${LM_STUDIO_API}/v1/chat/completions`);

    // Forward to LM Studio
    const response = await axios({
      method: 'post',
      url: `${LM_STUDIO_API}/v1/chat/completions`,
      headers: {
        'Content-Type': 'application/json'
      },
      data: req.body,
      timeout: TIMEOUT // 180 second timeout
    });

    console.log('LM Studio response status:', response.status);
    console.log('LM Studio response:', JSON.stringify(response.data, null, 2));

    // Send back the response
    res.status(response.status).json(response.data);
  } catch (error) {
    console.error('Error while forwarding to LM Studio:', error.message);

    if (error.response) {
      console.error('LM Studio response status:', error.response.status);
      console.error('LM Studio response:', error.response.data);

      res.status(error.response.status).json({
        error: 'LM Studio API Error',
        message: error.message,
        details: error.response.data
      });
    } else {
      res.status(500).json({
        error: 'Proxy Error',
        message: error.message
      });
    }
  }
});

// Alternative v1 endpoint for direct compatibility
app.post('/v1/chat/completions', async (req, res) => {
  try {
    console.log(`Forwarding to LM Studio at: ${LM_STUDIO_API}/v1/chat/completions`);

    // Forward to LM Studio
    const response = await axios({
      method: 'post',
      url: `${LM_STUDIO_API}/v1/chat/completions`,
      headers: {
        'Content-Type': 'application/json'
      },
      data: req.body,
      timeout: TIMEOUT // 180 second timeout
    });

    console.log('LM Studio response status:', response.status);
    res.status(response.status).json(response.data);
  } catch (error) {
    console.error('Error while forwarding to LM Studio:', error.message);

    if (error.response) {
      res.status(error.response.status).json(error.response.data);
    } else {
      res.status(500).json({
        error: 'Proxy Error',
        message: error.message
      });
    }
  }
});

// Health check endpoint
app.get('/test', (req, res) => {
  res.json({
    status: 'ok',
    message: 'Proxy server is running',
    endpoints: {
      chatCompletions: '/api/chat/completions',
      altChatCompletions: '/v1/chat/completions',
      healthCheck: '/test',
      apiConnectionTest: '/test-api-connection'
    },
    lmStudioTarget: LM_STUDIO_API
  });
});

// API connection test endpoint
app.get('/test-api-connection', async (req, res) => {
  try {
    console.log(`Testing connection to LM Studio API at: ${LM_STUDIO_API}`);

    // Try to connect to the LM Studio API
    const response = await axios({
      method: 'get',
      url: `${LM_STUDIO_API}/v1/models`,
      timeout: 5000 // 5 second timeout for the test
    });

    console.log('LM Studio API connection test successful');
    console.log('Available models:', JSON.stringify(response.data, null, 2));

    res.json({
      status: 'ok',
      message: 'Successfully connected to LM Studio API',
      models: response.data
    });
  } catch (error) {
    console.error('Error connecting to LM Studio API:', error.message);

    let errorDetails = {
      error: 'API Connection Error',
      message: error.message
    };

    if (error.code === 'ECONNREFUSED') {
      errorDetails.suggestion = 'Make sure LM Studio is running and the API is enabled on port 1234';
    }

    if (error.response) {
      errorDetails.statusCode = error.response.status;
      errorDetails.data = error.response.data;
    }

    res.status(500).json(errorDetails);
  }
});

// Start the server
app.listen(PORT, () => {
  console.log(`=== LM Studio Proxy Server ===`);
  console.log(`Proxy server running on http://localhost:${PORT}`);
  console.log(`Proxying requests to ${LM_STUDIO_API}`);
  console.log(`Available endpoints:`);
  console.log(`- API Endpoint: http://localhost:${PORT}/api/chat/completions`);
  console.log(`- Alt Endpoint: http://localhost:${PORT}/v1/chat/completions`);
  console.log(`- Health Check: http://localhost:${PORT}/test`);
  console.log(`=============================`);
});