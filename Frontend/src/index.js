import React from 'react';
import ReactDOM from 'react-dom/client';
import {BrowserRouter, Routes, Route} from 'react-router-dom';
import './index.css';
import App from './App';
import reportWebVitals from './reportWebVitals';
import LocalidadPage from './pages/localidad/LocalidadPage.js';
import InquilinoPage from './pages/inquilino/InquilinoPage.js';
import PropiedadPage from './pages/propiedades/PropiedadPage.js';
import ReservaPage from './pages/reserva/ReservaPage.js';
import TipoPropiedadPage from './pages/tipoPropiedad/TipoPropiedadPage.js';
import HeaderComponent from './components/HeaderComponent.js';
import FooterComponent from './components/FooterComponent.js';
import NavbarComponent from './components/NavbarComponent.js';

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(
  <React.StrictMode>
    <BrowserRouter>
    
      <Routes>
        <Route path = "/localidad" element ={<LocalidadPage/>}/>
        <Route path = "/inquilino" element ={<InquilinoPage/>}/>
        <Route path = "/propiedades" element ={<PropiedadPage/>}/>
        <Route path = "/tipoPropiedad" element ={<TipoPropiedadPage/>}/>
        <Route path = "/reserva" element ={<ReservaPage/>}/>
      </Routes>
      <HeaderComponent/>
      <NavbarComponent/>
      <FooterComponent/>
    </BrowserRouter> 
  </React.StrictMode>
);

// If you want to start measuring performance in your app, pass a function
// to log results (for example: reportWebVitals(console.log))
// or send to an analytics endpoint. Learn more: https://bit.ly/CRA-vitals
reportWebVitals();
