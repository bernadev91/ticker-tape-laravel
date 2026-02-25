export async function fetchCalculations() {
    const response = await window.axios.get('/api/calculations');

    return response.data.calculations;
}

export async function createCalculation(expression) {
    const response = await window.axios.post('/api/calculations', { expression });

    return response.data.calculation;
}

export async function deleteCalculation(id) {
    await window.axios.delete(`/api/calculations/${id}`);
}

export async function clearCalculations() {
    await window.axios.delete('/api/calculations');
}


