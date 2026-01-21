import './App.css'
import { BrowserRouter, Routes, Route } from 'react-router-dom'
import Landing from './Pages/Landing'
import { Copilot } from './Pages/copilot/Copilot'
import CommunityPage from './Pages/community/Community'
import Login from './Pages/Login/Login'
import Signup from './Pages/Signup'
import ProtectedRoutes from './Pages/components/ProtectedRoutes'
import ProfilePage from './Pages/profile/Profile'
import AboutPage from './Pages/AboutUs'
import SettingsPage from './Pages/Settings/Settings'

// ADD THE ABILITY TO SEND USER WORKFLOWS TO ADD ON IT/FIX IT - HARD - BACKEND HEAVY
// ADD THE ABILITY TO CREATE CUSTOM NODES - VERY HARD - F/B HEAVY ON BOTH
// ADD THE ABILITY TO SAVE CREDENTIALS OR FIGURE OUT A WAY TO DO IT AUTOMATICALLY - HARD F/B HEAVY ON BOTH
// Fix Retry taking so long to kick start again. Add Edit Message ability
// APPROX TIME : 8 DAYS TO FINISH (EXCLUDING ENHANCING THE AI'S ABILITY TO GENERATE WORKFLOWS)

// day 20
// 7 days left to have a fully functioning and clean website
// objectives for today before hitting 12 am:
// - Fix linting issues which includes cleaning types
// Clean the whole frontend where it needs to be cleaned, all except copilot pages
// merge branch with dev
// Move to the next branch for copilot
// Remove ranking module and just take top 5 answers for every node
// Clean the copilot frontend / backend files
// merge to main 
// fix CV 
// For after 12 am
// Deploy the website
// Create CI / CD
// push to main
// victory dance

// FIXES:
// FIX AVATAR UPLOADS I CAN'T BE ASSED MAN

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
