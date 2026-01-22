import './App.css'
import { BrowserRouter, Routes, Route } from 'react-router-dom'
import Landing from './Pages/Landing'
import { Copilot } from './Pages/copilot/Copilot'
import CommunityPage from './Pages/community/Community'
import Login from './Pages/Login/Login'
import Signup from './Pages/Signup/Signup'
import ProtectedRoutes from './Pages/components/ProtectedRoutes'
import ProfilePage from './Pages/profile/Profile'
import AboutPage from './Pages/AboutUs'
import SettingsPage from './Pages/Settings/Settings'

// ADD THE ABILITY TO SEND USER WORKFLOWS TO ADD ON IT/FIX IT - HARD - BACKEND HEAVY
// ADD THE ABILITY TO CREATE CUSTOM NODES - VERY HARD - F/B HEAVY ON BOTH
// ADD THE ABILITY TO SAVE CREDENTIALS OR FIGURE OUT A WAY TO DO IT AUTOMATICALLY - HARD F/B HEAVY ON BOTH
// USE LANGCHAIN

// day 22 I am fucked beyond comprehension
// - add loading handling 
// - test all functionalities before merge
// merge branch with dev
// Move to the next branch for copilot
// Remove ranking module and just take top 5 answers for every node
// Test tracing and generation
// Add the abiilty to send a workflow
// Clean the copilot frontend / backend files
// merge to main 
// fix CV 
// For after 12 am
// Deploy the website
// Create CI / CD
// push to main
// victory dance

// issues to solve
// posts not apearing immediatly after posting
// following/ Followers count not increasing as we follow
// white screen appearing on reload then page appears
// loading state for comments



function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Landing />} />
        <Route path="/login" element={<Login />} />
        <Route path="/signup" element={<Signup />} />

        <Route path="/copilot" element={
          <ProtectedRoutes>
            <Copilot />
          </ProtectedRoutes>
        }/>
        <Route path="/community" element={
          <ProtectedRoutes>
            <CommunityPage />
          </ProtectedRoutes>
        }/>
        <Route path="/profile/:userId?" element={
          <ProtectedRoutes>
            <ProfilePage />
          </ProtectedRoutes>
        }/>
        <Route path="/settings" element={
          <ProtectedRoutes>
            <SettingsPage />
          </ProtectedRoutes>
        }/>
        <Route path="/aboutus" element={<AboutPage />} />
      </Routes>
    </BrowserRouter>
  )
}

export default App
