using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows;
using System.Windows.Controls;
using System.Windows.Data;
using System.Windows.Documents;
using System.Windows.Input;
using System.Windows.Media;
using System.Windows.Media.Imaging;
using System.Windows.Shapes;

namespace BootsApp
{
    /// <summary>
    /// Логика взаимодействия для GuestMainWindow.xaml
    /// </summary>
    public partial class GuestMainWindow : Window
    {
        public bootsEntities2 _context;
        public GuestMainWindow()
        {
            InitializeComponent();
            _context = bootsEntities2.GetContext();
            DgTovar.ItemsSource = _context.Tovars.ToList();
        }

        private void Button_Click(object sender, RoutedEventArgs e)
        {
            MainWindow mw = new MainWindow();
            mw.Show();
            this.Close();
        }
    }
}
