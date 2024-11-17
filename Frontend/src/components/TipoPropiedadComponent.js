import React, {useState, useEffect} from 'react';
import Editar from "./EditarTipoPropiedad";
import Eliminar from "./EliminarTipoPropiedad";

const TipoPropiedadComponent = () => {
    
    const [data, setData] = useState([]);
    const [error, setError] = useState (null);   
    
    useEffect (() => {
        const fetchData = async () => {
            try {
                const response = await fetch('https://localhost/tipos_propiedad');
                debugger;
                if (!response.ok){
                    throw new Error();
                }
                const result = await response.json();
                setData(result);
            } catch(error){
                setError(error.message || 'Ha ocurrido un error');
            }
        }
        fetchData()
    }, []);

    if (error){
        return <div>Error: {error}</div>
    }
    
    return (
    <div>
        <h1>Listado de los tipos de propiedad</h1>
        <ul>
            {data.map((item, index) => (
                <li key={index}> {item.id} - {item.nombre} <Editar/> <Eliminar/></li>
            ))}
        </ul>
    </div>
    );
}

export default TipoPropiedadComponent;