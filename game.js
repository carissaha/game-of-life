const gridSize = 30;
let grid = [];
let generation = 0;
let intervalId = null;

function createGrid() {
  const gridElement = document.getElementById("grid");
  gridElement.innerHTML = '';
  grid = [];

  for (let row = 0; row < gridSize; row++) {
    const rowArr = [];
    for (let col = 0; col < gridSize; col++) {
      const cell = document.createElement("div");
      cell.classList.add("cell");
      cell.dataset.row = row;
      cell.dataset.col = col;

      cell.addEventListener("click", () => {
        cell.classList.toggle("alive");
      });

      gridElement.appendChild(cell);
      rowArr.push(0);
    }
    grid.push(rowArr);
  }
}

function getNextState(row, col) {
  const neighbors = [
    [-1, -1], [-1, 0], [-1, 1],
    [ 0, -1],         [ 0, 1],
    [ 1, -1], [ 1, 0], [ 1, 1]
  ];

  let aliveNeighbors = 0;

  neighbors.forEach(([dx, dy]) => {
    const newRow = row + dx;
    const newCol = col + dy;

    if (newRow >= 0 && newRow < gridSize && newCol >= 0 && newCol < gridSize) {
      const index = newRow * gridSize + newCol;
      const neighborCell = document.querySelectorAll('.cell')[index];
      if (neighborCell.classList.contains('alive')) {
        aliveNeighbors++;
      }
    }
  });

  const index = row * gridSize + col;
  const cell = document.querySelectorAll('.cell')[index];
  const isAlive = cell.classList.contains('alive');

  if (isAlive && (aliveNeighbors < 2 || aliveNeighbors > 3)) {
    return false;
  }
  if (!isAlive && aliveNeighbors === 3) {
    return true;
  }
  return isAlive;
}

function nextGen() {
  const newStates = [];

  for (let row = 0; row < gridSize; row++) {
    for (let col = 0; col < gridSize; col++) {
      newStates.push(getNextState(row, col));
    }
  }

  const cells = document.querySelectorAll('.cell');
  newStates.forEach((alive, i) => {
    if (alive) {
      cells[i].classList.add('alive');
    } else {
      cells[i].classList.remove('alive');
    }
  });

  generation++;
  document.getElementById('generation').textContent = generation;
}

function startGame() {
  if (!intervalId) {
    intervalId = setInterval(nextGen, 200);
  }
}

function stopGame() {
  clearInterval(intervalId);
  intervalId = null;
}

function resetGame() {
  stopGame();
  const cells = document.querySelectorAll('.cell');
  cells.forEach(cell => cell.classList.remove('alive'));
  generation = 0;
  document.getElementById('generation').textContent = generation;
}

function advance23() {
  for (let i = 0; i < 23; i++) {
    nextGen();
  }
}

function loadPattern(patternName) {
  resetGame();
  const cells = document.querySelectorAll('.cell');

  if (patternName === 'block') {
    const positions = [
      [14, 14], [14, 15],
      [15, 14], [15, 15]
    ];
    activatePattern(positions);
  }

  if (patternName === 'blinker') {
    const positions = [
      [14, 13], [14, 14], [14, 15]
    ];
    activatePattern(positions);
  }

  if (patternName === 'beacon') {
    const positions = [
      [13,13], [13,14],
      [14,13],
      [15,16],
      [16,15], [16,16]
    ];
    activatePattern(positions);
  }

  if (patternName === 'toad') {
    const positions = [
      [14, 14], [14, 15], [14, 16],
      [15, 13], [15, 14], [15, 15]
    ];
    activatePattern(positions);
  }

  if (patternName === 'glider') {
    const positions = [
      [13, 14],
      [14, 15],
      [15, 13], [15, 14], [15, 15]
    ];
    activatePattern(positions);
  }
}

function activatePattern(positions) {
  const cells = document.querySelectorAll('.cell');
  positions.forEach(([row, col]) => {
    const index = row * gridSize + col;
    if (cells[index]) {
      cells[index].classList.add('alive');
    }
  });
}

window.onload = createGrid;
