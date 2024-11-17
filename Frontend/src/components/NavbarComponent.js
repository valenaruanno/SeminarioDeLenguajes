import React from 'react';
import { NavLink } from 'react-router-dom';
import InquilinoPage from '../../src/pages/inquilino/InquilinoPage.js';
import LocalidadPage from '../../src/pages/localidad/LocalidadPage.js';
import PropiedadPage from '../../src/pages/propiedades/PropiedadPage.js';
import ReservaPage from '../../src/pages/reserva/ReservaPage.js';
import TipoPropiedadPage  from '../../src/pages/tipoPropiedad/TipoPropiedadPage.js';

const NavbarComponent = ()=> {
    return (
        <div> 
            <nav>  
                <ul>
                    <li>
                        <NavLink to = "/inquilino">
                            Inquilino
                        </NavLink>
                    </li>
                    <li>
                        <NavLink to = "/localidad">
                            Localidad
                        </NavLink>
                    </li>
                    <li>
                        <NavLink to = "/propiedad">
                            Propiedad
                        </NavLink>
                    </li>
                    <li>
                        <NavLink to = "/tipoPropiedad">
                            TipoPropiedad
                        </NavLink>
                    </li>
                    <li>
                        <NavLink to = "/reserva">
                            Reserva
                        </NavLink>
                    </li>
                </ul>
            </nav> 
            
        </div>
    );
  
};

export default NavbarComponent; 
