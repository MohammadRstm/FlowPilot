import './App.css'
import { BrowserRouter, Routes, Route } from 'react-router-dom'
import Landing from './Pages/Landing'

function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Landing />} />
      </Routes>
    </BrowserRouter>
  )
}

export default App
